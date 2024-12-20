<?php

namespace App\Service;

use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use MaxMind\Db\Reader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class UserService
{
    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly ParameterBagInterface $params,
        private                                $geoip = new Reader('/media/GeoLite2-Country.mmdb'),
        )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getUserData($token)
    {
        $response = $this->client->request('GET',
                                            $this->params->get('URL_USERDATA'),
                                            [
                                                'headers' => [
                                                    'Authorization' => str_replace('Bearer ', '', $token),
                                                ]
                                            ]
        );

        if($response->getStatusCode() != 200){
            throw new HttpException($response->getStatusCode());
        }

        $body = json_decode($response->getContent());

        if(!$body->status || $response->getStatusCode()!= 200){
            throw new HttpException(404, "not found");
        }

        $rawData = $body->data;
        return $rawData[0];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getUserDataByKey($key)
    {
        $url = $this->params->get('URL_KEYGROUP').$key;
        $response = $this->client->request('GET',
                                            $url
        );

        if($response->getStatusCode() != 200){
            throw new HttpException($response->getStatusCode());
        }

        $body = json_decode($response->getContent());

        if(!$body->status || $response->getStatusCode()!= 200){
            throw new HttpException(404, "not found");
        }

        return $body->data;
    }

    /**
     * @throws InvalidDatabaseException
     */
    public function getCountryCode(Request $request): string
    {
        $ip = $request->getClientIp();
        // if Header X-Force-IP contains correct address - take IP from Header
        if (filter_var($request->headers->Get("X-Force-IP"), FILTER_VALIDATE_IP)) {
            $ip = $request->headers->Get("X-Force-IP");
        }

        $geoipData = $this->geoip->get($ip);
        return strtoupper($geoipData['country']['iso_code'] ?? $this->params->get('DEFAULT_COUNTRY'));
    }
}