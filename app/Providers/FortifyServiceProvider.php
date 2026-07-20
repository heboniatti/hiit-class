<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure how login credentials are resolved.
     *
     * Users sign in with a phone number and a six digit PIN. The PIN lives in
     * the "password" column, so the "hashed" cast keeps applying. Doing the
     * lookup here is also what lets a deactivated account be turned away
     * before a session is ever created.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $phone = User::normalizePhone((string) $request->input('phone'));

            $user = User::query()->where('phone', $phone)->first();

            if (! $user || ! $user->isActive()) {
                return null;
            }

            if (! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register'));
    }

    /**
     * Configure rate limiting.
     *
     * Throttling the phone and IP pair is what temporarily locks someone out
     * after repeated wrong PINs.
     */
    private function configureRateLimiting(): void
    {
        // Fortify has no limiter hook for its register route, and adding one to
        // fortify.middleware would throttle the login route too — which would
        // lock out a whole gym signing in from one network. Registration is
        // rate limited inside CreateNewUser instead.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = User::normalizePhone((string) $request->input('phone')).'|'.$request->ip();

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
