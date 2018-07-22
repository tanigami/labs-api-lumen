<?php

namespace App\Http\Middleware;

use Auth0\SDK\JWTVerifier;
use Closure;
use Illuminate\Http\Request;

class Auth0Middleware
{
    /**
     * @var JWTVerifier
     */
    private $verifier;

    /**
     * @param JWTVerifier $JwtVerifier
     */
    public function __construct(JWTVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
//        if(is_null($token)) {
//
//            return $next($request);
//        }
        $subject = $this->retrieveAndValidateToken($token);
        $request->auth = $subject;

        return $next($request);
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function retrieveAndValidateToken(string $token)
    {
        try {
            $decoded = $this->verifier->verifyAndDecode($token);

            return $decoded->sub;
        } catch (\Auth0\SDK\Exception\CoreException $e) {
            if ($e->getMessage() === 'Expired token') {
                abort(401);
            }
        }
    }
}