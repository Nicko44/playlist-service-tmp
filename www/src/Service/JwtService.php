<?php
namespace App\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JwtService
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    )
    {
    }

    public function getBearerToken(?string $headerAuthorization): ?string
    {
        return preg_match("/Bearer\s((.*)\.(.*)\.(.*))/", $headerAuthorization, $matches)
            ? $matches[1]
            : null;
    }

    public function decodeJwtToken(string $token): stdClass
    {
        $key = null;
        if ('RS256' == $this->getJwtAlgorithm($token)){
            $key = new Key($this->parameterBag->get("JWT_PUBLIC_CERT"), 'RS256');
        } elseif ('HS256' == $this->getJwtAlgorithm($token) ) {
            $key = new Key($this->parameterBag->get("JWT_SECRET"), 'HS256');
        }

        if (!$key)
        {
            throw new HttpException(401, 'Invalid JWT algorithm');
        }

        try {
            return JWT::decode($token, $key);
        } catch (Exception $e) {
            throw new HttpException(401, $e->getMessage());
        }

    }

    function getJwtAlgorithm(string $jwt): ?string
    {
        $header = json_decode(base64_decode(explode('.', $jwt)[0] ?? null));
        return $header?->alg ?? null;
    }
}