<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\User;
use App\Services\UrgentMessages\UrgentAlertGatewayInterface;
use App\Services\UrgentMessages\UrgentAlertManager;
use App\Services\UrgentMessages\UrgentMessageGateway;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->app->singleton(UrgentAlertManager::class);
        $this->app->bind(UrgentMessageGateway::class, UrgentAlertManager::class);
        $this->app->bind(UrgentAlertGatewayInterface::class, UrgentAlertManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePermissionGates();
        $this->configureMorphMap();
    }

    /**
     * Stable aliases for polymorphic columns (e.g. the conflict override audit's schedulable).
     */
    protected function configureMorphMap(): void
    {
        Relation::morphMap([
            'course_session' => CourseSession::class,
            'exam' => Exam::class,
        ]);
    }

    /**
     * Register permission-based gates. Permissions are the sole gate of check.
     */
    protected function configurePermissionGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission)
            );
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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
            : null,
        );
    }
}
