<?php

use App\Http\Controllers\Admin\ExpenseTypeController;
use App\Http\Controllers\Admin\AutoRuleController;
use App\Http\Controllers\Admin\PaymentAccountController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\PaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/payment-requests')
    ->middleware('auth')
    ->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('payment-requests', [PaymentRequestController::class, 'index'])->name('payment-requests.index');
    Route::post('payment-requests', [PaymentRequestController::class, 'store'])->name('payment-requests.store');
    Route::put('payment-requests/{paymentRequest}', [PaymentRequestController::class, 'update'])->name('payment-requests.update');
    Route::get('admin/expense-types', [ExpenseTypeController::class, 'index'])->name('admin.expense-types.index');
    Route::post('admin/expense-types', [ExpenseTypeController::class, 'store'])->name('admin.expense-types.store');
    Route::put('admin/expense-types/{expenseType}', [ExpenseTypeController::class, 'update'])->name('admin.expense-types.update');
    Route::delete('admin/expense-types/{expenseType}', [ExpenseTypeController::class, 'destroy'])->name('admin.expense-types.destroy');
    Route::post('admin/expense-types/{expenseType}/categories', [ExpenseTypeController::class, 'storeCategory'])->name('admin.expense-types.categories.store');
    Route::put('admin/expense-types/{expenseType}/categories/{expenseCategory}', [ExpenseTypeController::class, 'updateCategory'])->name('admin.expense-types.categories.update');
    Route::delete('admin/expense-types/{expenseType}/categories/{expenseCategory}', [ExpenseTypeController::class, 'destroyCategory'])->name('admin.expense-types.categories.destroy');
    Route::get('admin/auto-rules', [AutoRuleController::class, 'index'])->name('admin.auto-rules.index');
    Route::post('admin/auto-rules', [AutoRuleController::class, 'store'])->name('admin.auto-rules.store');
    Route::put('admin/auto-rules/{autoRule}', [AutoRuleController::class, 'update'])->name('admin.auto-rules.update');
    Route::delete('admin/auto-rules/{autoRule}', [AutoRuleController::class, 'destroy'])->name('admin.auto-rules.destroy');

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('payment-accounts', [PaymentAccountController::class, 'index'])->name('admin.payment-accounts.index');
        Route::post('payment-accounts', [PaymentAccountController::class, 'store'])->name('admin.payment-accounts.store');
        Route::put('payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('admin.payment-accounts.update');
        Route::delete('payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('admin.payment-accounts.destroy');

        Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::patch('users/{user}/block', [UserController::class, 'block'])->name('admin.users.block');
        Route::patch('users/{user}/unblock', [UserController::class, 'unblock'])->name('admin.users.unblock');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
});

require __DIR__.'/settings.php';
