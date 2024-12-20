<?php

namespace App\Controller;

use App\Service\ChannelsService;
use App\Service\UserService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use OpenApi\Annotations as OA;


class AccessController extends AbstractController
{
    public function __construct(
        private readonly ChannelsService $channelsService,
        private readonly UserService     $userService,
        private readonly CacheInterface  $cache,
    )
    {
    }

    /**
     * @Route("v1/access/{key}", name="getChannelsByKey", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Channels array by Key",
     * )
     * @OA\Tag(name="access")
     * @throws InvalidArgumentException
     */
    public function getChannelsByKey($key, Request $request): Response
    {
        $country = $this->userService->getCountryCode($request);

        $key = strtoupper($key);
        $cacheUserKey = "KEY-" . $key;

        $user = $this->cache->get($cacheUserKey, function () use ($key) {
            return $this->userService->getUserDataByKey($key);
        });

        $userGroup = $user->group ?? $this->getParameter('DEFAULT_USER_GROUP');
        $adult = $user->adult ?? $this->getParameter('DEFAULT_ADULT');

        $platform = $this->getParameter('DEFAULT_PLATFORM');

        return $this->json(
            [
                "status" => "OK",
                "result" => $this->getAccessChannels($country, $platform, $userGroup, $adult)
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Route("v1/access", name="getChannels", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Channels array by params",
     * )
     * @OA\Tag(name="access")
     * @throws InvalidArgumentException
     */
    public function getChannels(Request $request): Response
    {
        $country = strtoupper($request->query->get("country") ?? $this->getParameter('DEFAULT_COUNTRY'));
        $userGroup = $request->query->get("user_group") ?? $this->getParameter('DEFAULT_USER_GROUP');
        $adult = $request->query->get("adult") ?? $this->getParameter('DEFAULT_ADULT');
        $platform = $request->query->get("platform") ?? $this->getParameter('DEFAULT_PLATFORM');

        return $this->json(
            [
                "status" => "OK",
                "result" => $this->getAccessChannels($country, $platform, $userGroup, $adult)
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getAccessChannels($country, $platform, $userGroup, $adult): array
    {
        $cachePlaylistKey = strtoupper(sprintf('PLAYLIST-V1-%s-%s-%s-%s', $country, $platform, $userGroup, $adult));
        $channels = $this->cache->get($cachePlaylistKey, function () use ($country, $platform, $userGroup, $adult) {
            return $this->channelsService->getChannelsByAccessRules($country,
                $platform,
                $userGroup,
                $adult
            );
        });

        $result = [];
        foreach ($channels as $elem) {
            $result[] = $elem->getId();
        }
        return $result;
    }
}