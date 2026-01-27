<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\PaymentAccount;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_update_draft_requests(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $type = ExpenseType::create(['name' => 'Type A']);
        $category = ExpenseCategory::create(['name' => 'Category A']);
        $account = PaymentAccount::create(['name' => 'Account A']);

        $draft = PaymentRequest::create([
            'user_id' => $user->id,
            'expense_type_id' => $type->id,
            'expense_category_id' => $category->id,
            'requisites' => 'Draft requisites',
            'amount' => 100,
            'ready_for_payment' => false,
            'paid' => false,
            'paid_account_id' => $account->id,
        ]);
        $draft->participants()->syncWithoutDetaching([$user->id]);

        $ready = PaymentRequest::create([
            'user_id' => $user->id,
            'expense_type_id' => $type->id,
            'expense_category_id' => $category->id,
            'requisites' => 'Ready requisites',
            'amount' => 200,
            'ready_for_payment' => true,
            'paid' => false,
            'paid_account_id' => $account->id,
        ]);
        $ready->participants()->syncWithoutDetaching([$user->id]);

        $this->actingAs($user)
            ->put("/payment-requests/{$draft->id}", [
                'expense_type_id' => $type->id,
                'expense_category_id' => $category->id,
                'requisites' => 'Updated requisites',
                'amount' => 150,
                'purchase_reference' => 'PO-1',
                'paid_account_id' => $account->id,
                'receipt_url' => 'https://example.test/receipt',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->put("/payment-requests/{$ready->id}", [
                'expense_type_id' => $type->id,
                'expense_category_id' => $category->id,
                'requisites' => 'Blocked update',
                'amount' => 250,
                'purchase_reference' => 'PO-2',
                'paid_account_id' => $account->id,
                'receipt_url' => 'https://example.test/receipt',
            ])
            ->assertForbidden();
    }

    public function test_accountant_can_update_ready_requests(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $type = ExpenseType::create(['name' => 'Type B']);
        $category = ExpenseCategory::create(['name' => 'Category B']);

        $ready = PaymentRequest::create([
            'user_id' => $accountant->id,
            'expense_type_id' => $type->id,
            'expense_category_id' => $category->id,
            'requisites' => 'Ready requisites',
            'amount' => 200,
            'ready_for_payment' => true,
            'paid' => false,
        ]);
        $ready->participants()->syncWithoutDetaching([$accountant->id]);

        $this->actingAs($accountant)
            ->put("/payment-requests/{$ready->id}", [
                'expense_type_id' => $type->id,
                'expense_category_id' => $category->id,
                'requisites' => 'Updated by accountant',
                'amount' => 210,
                'purchase_reference' => null,
                'paid_account_id' => null,
                'receipt_url' => null,
            ])
            ->assertRedirect();
    }

    public function test_admin_can_update_paid_requests_and_history_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $type = ExpenseType::create(['name' => 'Type C']);
        $category = ExpenseCategory::create(['name' => 'Category C']);

        $paid = PaymentRequest::create([
            'user_id' => $admin->id,
            'expense_type_id' => $type->id,
            'expense_category_id' => $category->id,
            'requisites' => 'Paid requisites',
            'amount' => 300,
            'ready_for_payment' => true,
            'paid' => true,
        ]);
        $paid->participants()->syncWithoutDetaching([$admin->id]);

        $this->actingAs($admin)
            ->put("/payment-requests/{$paid->id}", [
                'expense_type_id' => $type->id,
                'expense_category_id' => $category->id,
                'requisites' => 'Admin update',
                'amount' => 350,
                'purchase_reference' => 'PO-3',
                'paid_account_id' => null,
                'receipt_url' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('payment_request_histories', 1);
        $history = PaymentRequestHistory::first();
        $this->assertSame('updated', $history->action);
    }
}
