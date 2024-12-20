<?php

namespace App\Security;

use App\Service\JwtService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly JwtService $jwtService,)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->get("Authorization") ?? false;
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->jwtService->getBearerToken($request->headers->get('Authorization')) ??
            throw new HttpException(401, 'No API token provided');

        $payload = $this->jwtService->decodeJwtToken($token);

        $userId = (int)($payload->uid ?? (int)$payload->id ??
            throw HttpException(401, 'Invalid JWT token'));

        return new SelfValidatingPassport(new UserBadge($userId));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw new HttpException(401, $exception->getMessage());
    }
}