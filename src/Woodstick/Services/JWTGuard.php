<?php

namespace Woodstick\Services;

use Woodstick\Services\RequestTrait;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard as GuardContract;
use Illuminate\Support\Facades\Log;

class JWTGuard implements GuardContract {

    use GuardHelpers, RequestTrait;
    
    /**
     *
     * @var \Woodstick\Services\JWTAuth; 
     */
    protected $jwtAuth;

    public function __construct(JWTAuth $jwtAuth, UserProvider $provider, Request $request) {
        $this->jwtAuth = $jwtAuth;
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user() {
        
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        
        if (! is_null($this->user)) {
            return $this->user;
        }
        
        $this->user = null;
        
        $tokenString = $this->jwtAuth->getToken();
        if($this->jwtAuth->verifyToken($tokenString)) {
            
            $this->user = $this->jwtAuth->checkToken($tokenString);
        }
        
        return $this->user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = []) {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param array $credentials
     * @param bool  $login
     *
     * @return mixed
     */
    public function attempt(array $credentials = []) {
        Log::debug('Attemp to authenticate');

        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return $this->login($user, $credentials);
        }

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed  $user
     * @param  array  $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials) {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Log a user into the application. Create access token for the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return token
     */
    public function login(Authenticatable $user) {
        $token = $this->jwtAuth->newAuthenticationToken($user);
        
        $this->setUser($user);
        
        return $token;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout() {
        
        $this->jwtAuth->invalidateToken();
        
        $this->user = null;
    }
}
