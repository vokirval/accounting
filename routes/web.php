<?php

use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseTypeController;
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

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('expense-types', [ExpenseTypeController::class, 'index'])->name('admin.expense-types.index');
        Route::post('expense-types', [ExpenseTypeController::class, 'store'])->name('admin.expense-types.store');
        Route::put('expense-types/{expenseType}', [ExpenseTypeController::class, 'update'])->name('admin.expense-types.update');
        Route::delete('expense-types/{expenseType}', [ExpenseTypeController::class, 'destroy'])->name('admin.expense-types.destroy');

        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories.index');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store');
        Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('admin.expense-categories.update');
        Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('admin.expense-categories.destroy');

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
