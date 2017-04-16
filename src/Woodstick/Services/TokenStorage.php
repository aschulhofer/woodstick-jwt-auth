<?php

namespace Woodstick\Services;

use Woodstick\Data\Model\JWTToken;

/**
 * TODO: better naming?
 */
class TokenStorage {
    
    public function addTokenData(array $tokenData) {
        JWTToken::create($tokenData);
    }
    
    /**
     * Removed the stored data for the given token.
     * 
     * @param string $token
     * 
     * @return int
     */
    public function removeTokenData($token) {
        return JWTToken::where('token', $token)->delete();
    }
    
    /**
     * Get the stored data for the given token.
     * 
     * @param string $token
     * 
     * @return \App\Services\Contracts\TokenData
     */
    public function getTokenData($token) {
        
//        try {
//            $tokenData = App\Data\Model\JWTToken::where('token', $token)->firstOrFail();
//            return $tokenData;
//        }
//        catch(Illuminate\Database\Eloquent\ModelNotFoundException $e) {
//            return null;
//        }
        
        return JWTToken::where('token', $token)->first();
    }
}
