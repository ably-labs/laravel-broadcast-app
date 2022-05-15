<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //create dummy user to support subscribing to private channels
        if (config('app.env') === 'local') {
            $user = \App\Models\User::newModelInstance([
                'name' => 'OtherTestUser',
                'email' => 'othertest@example.com',
                'password' => Hash::make('secretpass'),
            ]);
            Auth::login($user);
        }
    }
}
