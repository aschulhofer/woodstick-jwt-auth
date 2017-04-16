<?php

namespace Woodstick\Services;

use Illuminate\Http\Request;

/**
 * 
 */
trait RequestTrait {
    
    /**
     * Request to get the token from.
     * 
     * @var \Illuminate\Http\Request 
     */
    protected $request;
    
    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * 
     * @return $this
     */
    public function setRequest(Request $request) {
        $this->request = $request;

        return $this;
    }
}
