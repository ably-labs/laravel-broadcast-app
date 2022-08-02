<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if($this->app->request->has('pusher')) {
            config()->set('broadcasting.connections.ably.pusher_adapter', $this->app->request->get('pusher') == 1);
        }

        Broadcast::routes();
        require base_path('routes/channels.php');
    }
}
