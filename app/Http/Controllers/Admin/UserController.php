<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'blocked_at']),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return back();
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return back();
    }

    public function block(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->forceFill(['blocked_at' => now()])->save();

        return back();
    }

    public function unblock(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->forceFill(['blocked_at' => null])->save();

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $createdCount = $user->paymentRequests()->count();
        $participantCount = $user->participatingPaymentRequests()->count();
        $historyCount = $user->paymentRequestHistories()->count();

        if ($createdCount > 0 || $participantCount > 0 || $historyCount > 0) {
            return back()->withErrors([
                'user' => sprintf(
                    'Неможливо видалити користувача. Пов’язані записи: створені заявки: %d, участь у заявках: %d, історія змін: %d. Спочатку видаліть або змініть ці записи.',
                    $createdCount,
                    $participantCount,
                    $historyCount,
                ),
            ]);
        }

        $user->delete();

        return back();
    }
}
