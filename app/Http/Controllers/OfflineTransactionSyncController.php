<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
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
            return response()->json(['message' => 'Akun aktif berbeda dari akun yang membuat transaksi offline.'], 409);
        }

        $items = $request->input('transactions');
        if (! is_array($items) || count($items) > 50) {
            return response()->json(['message' => 'Maksimal 50 transaksi dapat disinkronkan sekaligus.'], 422);
        }

        $synced = [];
        $failed = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                $failed[] = ['client_id' => null, 'message' => 'Format transaksi offline tidak valid.'];
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
                $failed[] = ['client_id' => $item['client_id'] ?? null, 'message' => 'Data transaksi tidak valid. Periksa akun dan kategori.'];
                continue;
            }

            $data = $validator->validated();
            $date = \Illuminate\Support\Carbon::parse($data['date']);
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
                'title' => 'Transaksi offline tersinkron',
                'message' => ucfirst($data['type']).' sebesar '.$rp((int) $data['amount']).' berhasil disimpan.',
            ]));

            if ($budget && $budget->amount > 0) {
                $spentAfter = $spentBefore + (int) $data['amount'];
                if ($spentBefore < $budget->amount && $spentAfter >= $budget->amount) {
                    $request->user()->notify(new FinanceActivityNotification([
                        'title' => 'Budget terlampaui',
                        'message' => 'Pengeluaran kategori '.$budget->category->name.' mencapai '.$rp($spentAfter).' dari budget '.$rp($budget->amount).'.',
                    ]));
                } elseif ($spentBefore < $budget->amount * 0.8 && $spentAfter >= $budget->amount * 0.8) {
                    $request->user()->notify(new FinanceActivityNotification([
                        'title' => 'Budget hampir habis',
                        'message' => 'Pengeluaran kategori '.$budget->category->name.' mencapai '.$rp($spentAfter).' dari budget '.$rp($budget->amount).'.',
                    ]));
                }
            }

            $synced[] = $data['client_id'];
        }

        return response()->json(['synced' => $synced, 'failed' => $failed]);
    }
}
