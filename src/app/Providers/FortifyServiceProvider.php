<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
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
        Fortify::loginView(function () {
        return view('auth.login');
        });

        Fortify::authenticateUsing(function (Request $request) {
            if ($request->routeIs('admin.*')) {
                if (Auth::guard('admin')->attempt($request->only('email','password'))) {
                return Auth::guard('admin')->user();
                }
                return null;
            }

            if (Auth::guard('web')->attempt($request->only('email','password'))) {
                return Auth::guard('web')->user();
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });
    }
}
