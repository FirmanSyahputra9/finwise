<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatCallController;
use App\Http\Controllers\OfflineTransactionSyncController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Livewire\Analysis;
use App\Livewire\Categories;
use App\Livewire\Chat;
use App\Livewire\Dashboard;
use App\Livewire\Profile;
use App\Livewire\SavingsGoals;
use App\Livewire\Transactions\Index as TransactionsIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'))->name('home');

Route::get('/manifest.webmanifest', fn () => response()->json(config('pwa.manifest'))
    ->header('Content-Type', 'application/manifest+json; charset=utf-8')
    ->header('Cache-Control', 'no-cache, must-revalidate'))
    ->name('pwa.manifest');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'createAccount'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/notifications/push-subscription', [PushSubscriptionController::class, 'store'])->middleware('throttle:10,1')->name('notifications.push-subscription.store');
    Route::delete('/notifications/push-subscription', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:10,1')->name('notifications.push-subscription.destroy');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/analysis', Analysis::class)->name('analysis');
    Route::get('/transactions', TransactionsIndex::class)->name('transactions');
    Route::get('/categories', Categories::class)->name('categories');
    Route::get('/savings-goals', SavingsGoals::class)->name('savings-goals');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/chat', Chat::class)->name('chat');
    Route::get('/chat/{type}/{id}', Chat::class)->whereIn('type', ['direct', 'group'])->whereUuid('id')->name('chat.conversation');
    Route::get('/chat/attachments/{uuid}', ChatAttachmentController::class)->whereUuid('uuid')->name('chat.attachment');
    Route::post('/chat/{connectionId}/calls', [ChatCallController::class, 'start'])->whereNumber('connectionId')->middleware('throttle:10,1')->name('chat.calls.start');
    Route::get('/chat/calls/incoming', [ChatCallController::class, 'incoming'])->middleware('throttle:60,1')->name('chat.calls.incoming');
    Route::post('/chat/calls/{uuid}/accept', [ChatCallController::class, 'accept'])->whereUuid('uuid')->middleware('throttle:30,1')->name('chat.calls.accept');
    Route::post('/chat/calls/{uuid}/reject', [ChatCallController::class, 'reject'])->whereUuid('uuid')->middleware('throttle:30,1')->name('chat.calls.reject');
    Route::post('/chat/calls/{uuid}/end', [ChatCallController::class, 'end'])->whereUuid('uuid')->middleware('throttle:30,1')->name('chat.calls.end');
    Route::get('/chat/calls/{uuid}/signals', [ChatCallController::class, 'signals'])->whereUuid('uuid')->middleware('throttle:90,1')->name('chat.calls.signals');
    Route::post('/chat/calls/{uuid}/signals', [ChatCallController::class, 'sendSignal'])->whereUuid('uuid')->middleware('throttle:180,1')->name('chat.calls.signal');
    Route::post('/profile/photo', ProfilePhotoController::class)->middleware('throttle:10,1')->name('profile.photo');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/transactions/sync', OfflineTransactionSyncController::class)->middleware('throttle:30,1')->name('transactions.sync');
});
