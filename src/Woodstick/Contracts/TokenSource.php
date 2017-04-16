<?php

namespace Woodstick\Services\Contracts; 

use Illuminate\Http\Request;

/**
 * 
 */
interface TokenSource {
   
    /**
     * Returns the token from the given request.
     * 
     * @param Request $request
     * 
     * @return string
     */
    public function getToken(Request $request);
    
}
