<?php

namespace Woodstick\Services;

use Woodstick\Services\TokenProvider;
use Woodstick\Services\TokenStorage;

use Illuminate\Support\Facades\Log;

use Woodstick\JWT\Claim\Claim;
use Woodstick\JWT\Claim\IssuedAt;
use Woodstick\JWT\Claim\Subject;
use Woodstick\JWT\JWTLib;
use Woodstick\JWT\Token;

class JWTAuth {

    /**
     * The jwt library instance.
     * 
     * @var JWTLib
     */
    protected $jwtLib;
    
    /**
     *
     * @var TokenProvider 
     */
    protected $tokenProvider;
    
    /**
     *
     * @var TokenStorage 
     */
    protected $tokenStorage;
    
    public function __construct(JWTLib $jwtLib, TokenProvider $tokenProvider, TokenStorage $tokenStorage) {
        $this->jwtLib = $jwtLib;
        $this->tokenProvider = $tokenProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get token for current request as string or token object.
     * 
     * @param boolean $parse if set to false returns token as string
     * 
     * @return mixed
     */
    public function getToken($parse = false) {
        $tokenString = $this->tokenProvider->retrieveToken();
        
        if($parse) {
            return $this->parseToken($tokenString);
        }
        
        return $tokenString;
    }
    
    /**
     * Parses the given token string
     * 
     * @param string $tokenString
     * 
     * @return Token
     */
    public function parseToken($tokenString) {
        return $this->jwtLib->parse($tokenString);
    }
    
    /**
     * Parses the given token and verifies the signature.
     * 
     * @param string $tokenString
     * 
     * @return boolean
     */
    public function verifyToken($tokenString) {
        $token = $this->parseToken($tokenString);

        return $this->jwtLib->verify($token);
    }

    /**
     * Checks if the token is valid and returns the authenticated user or null
     * if invalid.
     *  
     * @param string $token
     * 
     * TODO: use Illuminate\Auth\Authenticatable
     * @return App\Data\Model\User|null
     */
    public function checkToken($token) {
        $tokenData = $this->tokenStorage->getTokenData($token);
        
        if(!$tokenData) {
            Log::debug(sprintf('No token data found for "%s"', $token));
            
            return null;
        }
        
        $user = $tokenData->user;
        
        Log::debug(sprintf('Found user "%s" for token "%s"', $user->getKey(), $token));
        
        return $user;
    }
    
    /**
     * TODO: use Illuminate\Auth\Authenticatable
     * @param \App\Data\Model\User $user
     *
     * @return \Woodstick\JWT\Token|null
     */
    public function authenticateUser($user) {

        $issuedAt = new \DateTime();
        $issuedAtTimestamp = $issuedAt->format('U');

        $claims = [
            new Claim("ntb", time() + 60),
            new Claim("exp", time() + 3600),
            new Subject("token"),
            new IssuedAt($issuedAtTimestamp),
            new Claim("data", [
                "email" => $user->email,
            ]),
        ];

        $token = $this->jwtLib->create($claims);

        $this->tokenStorage->addTokenData([
            'token' => strval($token),
            'user_id' => $user->id,
            'issued_at' => $issuedAt
        ]);
        
        return $token;
    }
    
    /**
     * TODO: use Illuminate\Auth\Authenticatable
     * @param \App\Data\Model\User $user
     *
     * @return \Woodstick\JWT\Token|null
     */
    public function newAuthenticationToken($user) {
        return $this->authenticateUser($user);
    }
    
    /**
     * Invalidates a token. If no value is given the token from the current 
     * request gets invalidated.
     * 
     * @param string $token
     */
    public function invalidateToken($token = null) {
        $tokenToInvalidate = $token ?: $this->getToken();
        
        $removedToken = $this->tokenStorage->removeTokenData($tokenToInvalidate);
        
        Log::debug(sprintf('Removed data for %s token', $removedToken));
    }
}
