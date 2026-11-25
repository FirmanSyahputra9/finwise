<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Notifications\FinanceActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OfflineTransactionSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ((int) $request->input('owner_id') !== (int) $request->user()->id) {
            return response()->json(['message' => __('ui.offline_wrong_user')], 409);
        }

        $items = $request->input('transactions');
        if (! is_array($items) || count($items) > 50) {
            return response()->json(['message' => __('ui.offline_max_batch')], 422);
        }

        $synced = [];
        $failed = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                $failed[] = ['client_id' => null, 'message_key' => 'invalidFormat', 'message' => __('ui.offline_invalid_format')];
                continue;
            }

            $validator = Validator::make($item, [
                'client_id' => ['required', 'uuid'],
                'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
                'amount' => ['required', 'integer', 'min:1'],
                'date' => ['required', 'date'],
                'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $request->user()->id)],
                'to_account_id' => [Rule::requiredIf(($item['type'] ?? null) === 'transfer'), 'nullable', 'different:account_id', Rule::exists('accounts', 'id')->where('user_id', $request->user()->id)],
                'category_id' => [Rule::requiredIf(($item['type'] ?? null) !== 'transfer'), 'nullable', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)->where('type', $item['type'] ?? '')],
                'note' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $failed[] = ['client_id' => $item['client_id'] ?? null, 'message_key' => 'invalidData', 'message' => __('ui.offline_invalid_data')];
                continue;
            }

            $data = $validator->validated();
            if (Transaction::where('client_id', $data['client_id'])->exists()) {
                $synced[] = $data['client_id'];
                continue;
            }

            $date = \Illuminate\Support\Carbon::parse($data['date']);
            if ($data['type'] === 'expense') {
                $monthlyLimit = (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')
                    ->where('user_id', $request->user()->id)
                    ->where('month', $date->month)
                    ->where('year', $date->year)
                    ->value('amount');

                if ($monthlyLimit > 0) {
                    $spentThisMonth = (int) Transaction::ofType(TransactionType::Expense)
                        ->inMonth($date->year, $date->month)
                        ->sum('amount');

                    if ($spentThisMonth + (int) $data['amount'] > $monthlyLimit) {
                        $remaining = max(0, $monthlyLimit - $spentThisMonth);
                        $failed[] = [
                            'client_id' => $data['client_id'],
                            'message_key' => 'expenseLimit',
                            'message_params' => ['amount' => number_format($remaining, 0, ',', '.')],
                            'message' => __('ui.expense_over_limit', ['amount' => number_format($remaining, 0, ',', '.') ]),
                        ];
                        continue;
                    }
                }
            }

            $budget = $data['type'] === 'expense'
                ? Budget::with('category')->where('category_id', $data['category_id'])->where('month', $date->month)->where('year', $date->year)->first()
                : null;
            $spentBefore = $budget?->spent() ?? 0;
            $transaction = Transaction::firstOrCreate(
                ['client_id' => $data['client_id']],
                [
                    'type' => $data['type'],
                    'amount' => $data['amount'],
                    'date' => $data['date'],
                    'account_id' => $data['account_id'],
                    'to_account_id' => $data['type'] === 'transfer' ? $data['to_account_id'] : null,
                    'category_id' => $data['type'] === 'transfer' ? null : $data['category_id'],
                    'note' => $data['note'] ?? null,
                ],
            );
            if (! $transaction->wasRecentlyCreated) {
                $synced[] = $data['client_id'];
                continue;
            }

            $rp = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');
            $request->user()->notify(new FinanceActivityNotification([
                'title_key' => 'notification_offline_synced',
                'message_key' => 'notification_offline_recorded',
                'message_params' => ['type' => $data['type'], 'amount' => $rp((int) $data['amount'])],
                'title' => __('ui.notification_offline_synced'),
                'message' => __('ui.notification_offline_recorded', ['type' => __('ui.'.$data['type']), 'amount' => $rp((int) $data['amount'])]),
            ]));

            if ($budget && $budget->amount > 0) {
                $spentAfter = $spentBefore + (int) $data['amount'];
                if ($spentBefore < $budget->amount && $spentAfter >= $budget->amount) {
                    $request->user()->notify(new FinanceActivityNotification([
                        'title_key' => 'notification_budget_reached',
                        'message_key' => 'notification_budget_detail',
                        'message_params' => ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)],
                        'title' => __('ui.notification_budget_reached'),
                        'message' => __('ui.notification_budget_detail', ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)]),
                    ]));
                } elseif ($spentBefore < $budget->amount * 0.8 && $spentAfter >= $budget->amount * 0.8) {
                    $request->user()->notify(new FinanceActivityNotification([
                        'title_key' => 'notification_budget_low',
                        'message_key' => 'notification_budget_detail',
                        'message_params' => ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)],
                        'title' => __('ui.notification_budget_low'),
                        'message' => __('ui.notification_budget_detail', ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)]),
                    ]));
                }
            }

            $synced[] = $data['client_id'];
        }

        return response()->json(['synced' => $synced, 'failed' => $failed]);
    }
}
