<?php

namespace Woodstick\Services;

use Woodstick\Services\Contracts\TokenSource as TokenSourceContract;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class TokenSource implements TokenSourceContract {
    
    protected $headerName = 'Authorization';
    
    protected $prefix = 'Bearer ';
    
    public function getToken(Request $request) {
        
        if($request->headers->has($this->headerName)) {
            $token = $request->header($this->headerName);
            
            Log::info(sprintf('Found token %s', $token));

            if(preg_match('/' . $this->prefix . '(.*)/i', $token, $matches)) {
                
                Log::info(sprintf('Found bearer token %s', $matches[1]));
                
                return $matches[1];
            }
        }
        
        return null;
    }
}
