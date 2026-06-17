<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        $this->configureDefaults();
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

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        RateLimiter::for('enrollments', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(10)->by($email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('payment-returns', function (Request $request): Limit {
            $enrollment = $request->route('enrollment');
            $enrollmentKey = is_object($enrollment) && method_exists($enrollment, 'getKey')
                ? $enrollment->getKey()
                : (string) $enrollment;

            return Limit::perMinute(10)->by($enrollmentKey.'|'.$request->ip());
        });
    }
}
