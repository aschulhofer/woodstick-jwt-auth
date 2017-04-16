<?php

namespace Woodstick\Services\Contracts; 

/**
 *
 */
interface TokenData {

    /**
     * Authentication data the token belongs to.
     * 
     * @return \Illuminate\Auth\Authenticatable
     */
    public function user();
}
