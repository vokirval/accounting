<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseTypeStoreRequest;
use App\Http\Requests\Admin\ExpenseTypeUpdateRequest;
use App\Http\Requests\Admin\ExpenseCategoryStoreRequest;
use App\Http\Requests\Admin\ExpenseCategoryUpdateRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTypeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ExpenseType::class);

        return Inertia::render('admin/expense-types/Index', [
            'items' => ExpenseType::query()
                ->with(['categories' => function ($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(ExpenseTypeStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', ExpenseType::class);

        ExpenseType::create($request->validated());

        return back();
    }

    public function update(ExpenseTypeUpdateRequest $request, ExpenseType $expenseType): RedirectResponse
    {
        $this->authorize('update', $expenseType);

        $expenseType->update($request->validated());

        return back();
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        $this->authorize('delete', $expenseType);

        $categoryCount = $expenseType->categories()->count();
        $requestCount = $expenseType->paymentRequests()->count();

        if ($categoryCount > 0 || $requestCount > 0) {
            return back()->withErrors([
                'delete' => sprintf(
                    'Неможливо видалити тип витрат. Пов’язані категорії: %d, пов’язані заявки: %d. Спочатку видаліть або змініть ці зв’язки.',
                    $categoryCount,
                    $requestCount,
                ),
            ]);
        }

        $expenseType->delete();

        return back();
    }

    public function storeCategory(ExpenseCategoryStoreRequest $request, ExpenseType $expenseType): RedirectResponse
    {
        $this->authorize('update', $expenseType);

        $expenseType->categories()->create($request->validated());

        return back();
    }

    public function updateCategory(
        ExpenseCategoryUpdateRequest $request,
        ExpenseType $expenseType,
        ExpenseCategory $expenseCategory
    ): RedirectResponse {
        $this->authorize('update', $expenseType);

        $category = $expenseType->categories()->whereKey($expenseCategory->id)->firstOrFail();
        $category->update($request->validated());

        return back();
    }

    public function destroyCategory(ExpenseType $expenseType, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('update', $expenseType);

        $category = $expenseType->categories()->whereKey($expenseCategory->id)->firstOrFail();
        $requestCount = $category->paymentRequests()->count();

        if ($requestCount > 0) {
            return back()->withErrors([
                'delete_category' => sprintf(
                    'Неможливо видалити категорію. Пов’язані заявки: %d. Спочатку видаліть або змініть ці заявки.',
                    $requestCount,
                ),
            ]);
        }
        $category->delete();

        return back();
    }
}
