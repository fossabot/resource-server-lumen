<?php

namespace App\Http\Middleware;

use App\Repositories\AccessTokenRepository;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $accessTokenRepository = new AccessTokenRepository();
        $publicKeyPath = storage_path('oauth-public.key');

        $server = new \League\OAuth2\Server\ResourceServer(
            $accessTokenRepository,
            $publicKeyPath
        );

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            $server->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
