<?php

namespace Sebdesign\StackSession;

use Illuminate\Support\ServiceProvider;

class StackSessionServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind('session.handlers', static function ($app) {
            $session = $app->make('session');
            $config = $session->getSessionConfig();
            $drivers = $config['drivers'] ?? [];

            foreach ($drivers ?: ['file'] as $driver) {
                yield $driver => $session->driver($driver)->getHandler();
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->make('session')->extend('stack', static function ($app) {
            return new StackSessionHandler($app->make('session.handlers'));
        });
    }
}
