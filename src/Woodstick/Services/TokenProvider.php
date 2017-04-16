<?php

namespace Woodstick\Services;

use Woodstick\Services\TokenSource;
use Woodstick\Services\RequestTrait;

use Illuminate\Http\Request;

/**
 *
 */
class TokenProvider {
    
    use RequestTrait;
    
    /**
     * @var \Woodstick\Services\TokenSource 
     */
    protected $tokenSource;
    
    /**
     * @var string 
     */
    protected $token;
    
    public function __construct(Request $request, TokenSource $tokenSource) {
        $this->request = $request;
        $this->tokenSource = $tokenSource;
    }
    
    /**
     * Retrieves the token from the current request.
     * 
     * @return string
     */
    public function retrieveToken() {
        if(!$this->token) {
            $this->token = $this->tokenSource->getToken($this->request);
        }
        
        return $this->token;
    }
}
