<?php

namespace App\Providers;

use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\PaymentAccount;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Policies\AdminOnlyPolicy;
use App\Policies\PaymentRequestPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(PaymentRequest::class, PaymentRequestPolicy::class);
        Gate::policy(ExpenseType::class, AdminOnlyPolicy::class);
        Gate::policy(ExpenseCategory::class, AdminOnlyPolicy::class);
        Gate::policy(PaymentAccount::class, AdminOnlyPolicy::class);
        Gate::policy(User::class, AdminOnlyPolicy::class);
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
