<?php

return [

    /**
     * 
     */
    'secret' => env('AUTH_AXT_JWT_SECRET'),
    
    /**
     * Available 'Hmac', 'Rsa', 'Ecdsa'
     */
    'signature' => env('AUTH_AXT_JWT_SIGNATURE', 'Hmac'),
    
    /**
     * 'Sha256', 'Sha384', 'Sha512'
     */
    'algo' => env('AUTH_AXT_JWT_ALGO', 'Sha256'),
    
];
