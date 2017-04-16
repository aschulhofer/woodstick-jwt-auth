<?php

namespace Woodstick\Providers;

use Woodstick\Services\JWTAuth;
use Woodstick\Services\JWTGuard as JWTAuthGuard;
use Woodstick\Services\TokenProvider;
use Woodstick\Services\TokenSource;
use Woodstick\Services\TokenStorage;

use Woodstick\JWT\JWTLib;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class JWTAuthServiceProvider extends ServiceProvider
{
    protected $configName = 'axt-jwt';
    
    /**
     * Get config values
     *
     * @param  string  $key
     * @param  string  $default
     *
     * @return mixed
     */
    protected function getConfig($key, $default = null) {
        return app('config')->get("$this->configName.$key", $default);
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Load configuration
        $this->app->configure($this->configName);
        
        $this->app->alias('app.jwt.library', JWTLib::Class);
        $this->app->alias('app.jwt.token.source', TokenSource::Class);
        $this->app->alias('app.jwt.token.provider', TokenProvider::Class);
        $this->app->alias('app.jwt.token.storage', TokenStorage::Class);
        $this->app->alias('app.jwt.service', JWTAuth::Class);
        
        $this->app->singleton('app.jwt.library', function ($app) {
            return new JWTLib(
                $this->getConfig('secret'),
                $this->getConfig('signature'),
                $this->getConfig('algo')
            );
        });
        
        $this->app->singleton('app.jwt.token.source', function ($app) {
            return new TokenSource();
        });
        
        $this->app->singleton('app.jwt.token.provider', function ($app) {
            $tokenProvider = new TokenProvider(
                $app['request'],
                $app['app.jwt.token.source']
            );
            
            // Refresh an instance of request on the given target and method.
            $this->app->refresh('request', $tokenProvider, 'setRequest');
            
            return $tokenProvider;
        });
        
        $this->app->singleton('app.jwt.token.storage', function ($app) {
            return new TokenStorage();
        });

        $this->app->singleton('app.jwt.service', function ($app) {
            return new JWTAuth(
                $app['app.jwt.library'],
                $app['app.jwt.token.provider'],
                $app['app.jwt.token.storage']
            );
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {

        // Define 'axt-jwt' guard
        $this->app['auth']->extend('axt-jwt', function ($app, $name, array $config) {
            
            $provider = $app['auth']->createUserProvider($config['provider']);
            
            $guard = new JWTAuthGuard(
                $app['app.jwt.service'],
                $provider,
                $app['request']
            );
            
            // Refresh an instance of request on the given target and method.
            $this->app->refresh('request', $guard, 'setRequest');

            Log::info('Setup JWT Guard {name}', ["name" => $name]);
            Log::info(sprintf('Setup JWT Guard %s', $name));

            return $guard;
        });
    }
}
