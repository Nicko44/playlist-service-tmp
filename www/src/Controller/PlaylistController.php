<?php

namespace App\Controller;

use App\Repository\ChannelRepository;
use App\Service\ChannelsService;
use App\Service\PlaylistService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;

class PlaylistController extends AbstractController
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private ChannelsService $channelsService,
        private PlaylistService $playlistService,
        private UserService $userService,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    )
    {
        $this->channelRepository = $channelRepository;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *      "/v2/playlist/json",
     *      name="getPlaylistByAccessToken",
     *      methods={"GET"}
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns Array Channels with specific properties and filter by rules",
     * )
     * @OA\Tag(name="playlist")
     * @Security(name="Authorization")
     */
    public function getPlaylistByAccessToken(Request $request): Response
    {
        $token = $request->headers->get('Authorization');

        $platform = $request->get('platform') ?? $this->getParameter('DEFAULT_PLATFORM');

        $userGroup = $this->getParameter('DEFAULT_USER_GROUP');
        $adult = $this->getParameter('DEFAULT_ADULT');

        $country = $this->userService->getCountryCode($request);

        if(isset($token)){
            $cacheTokenKey = strtoupper("KEY-".$token);

            $user = $this->cache->get($cacheTokenKey, function() use ($token){
                return $this->userService->getUserData($token);
            });

            $userGroup = $this->getParameter('DEFAULT_USER_GROUP');
            $language = $user->language ?? $this->getParameter('DEFAULT_LANGUAGE');
            $key = $user->ottkey;
        }

        $cachePlaylistKey = strtoupper(sprintf('PLAYLIST-V2-%s-%s-%s-%s-%s', $country, $platform, $userGroup, $language, $adult));

        $playlist = $this->cache->get($cachePlaylistKey, function() use ($country, $platform, $userGroup, $language, $key, $adult){
            $channels = $this->channelsService->getChannelsByAccessRules($country,
                $platform,
                $userGroup,
                $adult
            );
            return $this->playlistService->formatToJSON($channels, $key, $language);
        });

        return $this->json(
            [
                "status" => "OK",
                "result" => $playlist
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channels']
        );
    }

    /**
     * @Route("/v1/playlist",
     *      name="getPlaylistStatic",
     *      methods={"GET"},
     * )
     * @OA\Parameter(
     *     name="group",
     *     in="query",
     *     description="The group parameter is used for generate links in playlist must be int. Default is last.",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="language",
     *     in="query",
     *     description="The language parameter is used for generate links in playlist must be 2 letters code like en, uk, tr, ru. Default is ru.",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="scheme",
     *     in="query",
     *     description="The scheme parameter is used for generate links in playlist must be http or https. Default is http.",
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns static Playlist in JSON format - uses for old type APP"
     * )
     * @OA\Tag(name="playlist")
     */
    public function getPlaylistStatic(Request $request): Response
    {
        $country = $this->userService->getCountryCode($request);

        // get scheme from request
        $scheme = $request->query->get('scheme') ?? 'https';
        $scheme = strtolower($scheme) === 'http' ? 'http' : 'https';

        //this method return channel list for user with user_group 0, 1 with rules for user_group = 2
        $userGroup = $request->query->get('group') ?? $this->getParameter('DEFAULT_USER_GROUP');
        if ($userGroup < 2) $userGroup = 2;

        $adult = $request->query->get('adult') ?? $this->getParameter('DEFAULT_ADULT');

        $platform = strtolower($request->query->get('platform') ?? $this->getParameter('DEFAULT_PLATFORM'));
        $language = strtolower($request->query->get('language') ?? $this->getParameter('DEFAULT_LANGUAGE'));

        $cachePlaylistKey = strtoupper(sprintf('PLAYLIST-V1-%s-%s-%s-%s', $country, $platform, $userGroup, $adult));

        $channels = $this->cache->get($cachePlaylistKey, function() use ($country, $platform, $userGroup, $adult){
            return $this->channelsService->getChannelsByAccessRules($country,
                $platform,
                $userGroup,
                $adult
            );
        });

        $playlist = $this->playlistService->formatToStaticJSON($channels, $language, $scheme);

        return $this->json(
            [
                "status" => "success",
                "epg_url" => $this->getParameter('URL_EPG'),
                "channel_data" => $playlist
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channels']
        );

    }

    /**
     * @Route("/v1/playlist/{key}/{type}",
     *      name="getPlaylistByKey",
     *      methods={"GET"},
     *      requirements={"type"="(?i)m3u|siptv|ssiptv|t2|t2_m3u|samsung|webtv|e2_channels|e2_userbouquet|json", "key"="^[a-zA-Z0-9]{10,11}"}
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns specific Playlist files for different platforms(look at typeproperty).<br>Allowed types:<br>- m3u<br>- siptv<br>- ssiptv<br>- t2<br>- t2_m3u[DEPRECATED}<br>- samsung<br>- webtv<br>- e2_channels<br>- e2_userbouquet<br>- json",
     * )
     * @OA\Tag(name="playlist")
     */
    public function getPlaylistByKey($key, $type, Request $request): Response
    {
        $type = strtolower($type);
        $country = $this->userService->getCountryCode($request);

        $key = strtoupper($key);
        $cacheUserKey = "KEY-".$key;

        $user = $this->cache->get($cacheUserKey, function() use ($key){
            return $this->userService->getUserDataByKey($key);
        });

        $platform = strtoupper($this->getParameter('DEFAULT_PLATFORM'));

        $userGroup = (int)$user->group ?? $this->getParameter('DEFAULT_USER_GROUP');
        $language = (string)$user->language ?? $this->getParameter('DEFAULT_LANGUAGE');
        $adult = (bool)$user->adult ?? $this->getParameter('DEFAULT_ADULT');

        $cachePlaylistKey = strtoupper(sprintf('PLAYLIST-V1-%s-%s-%s-%s', $country, $platform, $userGroup, $adult));

        $channels = $this->cache->get($cachePlaylistKey, function() use ($country, $platform, $userGroup, $adult){
            return $this->channelsService->getChannelsByAccessRules($country,
                $platform,
                $userGroup,
                $adult
            );
        });

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);

        switch ($type) {
            case 'm3u':
                $playlist = $this->playlistService->formatToM3U($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'audio/mpegurl');
                $response->headers->set('Content-Disposition', 'attachment; filename=playlist.m3u');
                break;
            case 'siptv':
                $playlist = $this->playlistService->formatToSiptv($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->headers->set('Content-Type', 'audio/mpegurl; charset=UTF-8');
                $response->headers->set('Content-Disposition', 'attachment; filename=playlist.m3u');
                break;
            case 'ssiptv':
                $playlist = $this->playlistService->formatToSSiptv($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'audio/mpegurl');
                $response->headers->set('Content-Disposition', 'attachment; filename=playlist.m3u');
                break;
            case 't2':
                //old naming
            case 't2_m3u':
                $playlist = $this->playlistService->formatToT2($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'audio/mpegurl');
                $response->headers->set('Content-Disposition', 'attachment; filename=playlist.m3u');
                break;
            case 'samsung':
                $playlist = $this->playlistService->formatToSamsung($channels, $key);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'application/xml');
                $response->headers->set('Content-Disposition', 'attachment; filename=nStream.xml');
                break;
            case 'webtv':
                $playlist = $this->playlistService->formatToWebtv($channels, $key);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'application/xml');
                $response->headers->set('Content-Disposition', 'attachment; filename=webtv_usr.xml');
                break;
            case 'e2_channels':
                $playlist = $this->playlistService->formatToE2Channels($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'application/xml');
                $response->headers->set('Content-Disposition', 'attachment; filename=channels.xml');
                break;
            case 'e2_userbouquet':
                $playlist = $this->playlistService->formatToE2Bouquet($channels, $key, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'application/xml');
                $response->headers->set('Content-Disposition', 'attachment; filename=userbouquet.iptv.tv');
                break;
            case 'json':
                $playlist = $this->playlistService->formatToJSON($channels, $key, $language);
                $response->setContent(json_encode($playlist));
                $response->headers->set('Content-Type', 'application/json');
                break;
            default:
                throw new HttpException(404, "not found");
        }
        return $response;
    }
}