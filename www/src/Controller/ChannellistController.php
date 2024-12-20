<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Service\ChannelsService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class ChannellistController extends AbstractController
{
    public function __construct(
        private ChannelsService $channelsService,
        private UserService $userService,
        private CacheInterface $cache,
        )
    {
        $this->channelsService = $channelsService;
        $this->userService = $userService;
        $this->cache = $cache;
    }

    /**
     * @Route("v1/channellist", name="getChannellist", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Channels with hiden properties and filter by rules",
     *     @Model(type=Channel::class, groups={"channellist"})
     * )
     * @OA\Tag(name="channellist")
     */
    public function getChannellist(Request $request): Response
    {
        $country = $this->userService->getCountryCode($request);

        $cacheKey = strtoupper('CHANNELLIST-'.$country);

        $channellist = $this->cache->get($cacheKey, function() use ($country){
            $channels = $this->channelsService->getChannelsByAccessRules($country,
                                                                            $this->getParameter('DEFAULT_PLATFORM'),
                                                                            $this->getParameter('DEFAULT_USER_GROUP'),
                                                                            $this->getParameter('DEFAULT_ADULT')
                                                                        );
            $this->channelsService->formatToChannellist($channels);
            return $channels;
        });


        return $this->json(
            [
                "status" => "OK",
                "result" => $channellist
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channellist']
        );
    }

    /**
     * @Route("v1/channellist/count", name="getChannellistCount", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Count Channels by Resolutions with filter by rules",
     * )
     * @OA\Tag(name="channellist")
     */
    public function getChannellistCount(Request $request): Response
    {
        $country = $this->userService->getCountryCode($request);

        $cacheKey = strtoupper('CHANNELLIST-COUNT-'.$country);

        $channellistCount = $this->cache->get($cacheKey, function() use ($country){
            $channels = $this->channelsService->getChannelsByAccessRules($country,
                                                                        $this->getParameter('DEFAULT_PLATFORM'),
                                                                        $this->getParameter('DEFAULT_USER_GROUP'),
                                                                        $this->getParameter('DEFAULT_ADULT')
                                                                    );
            return $this->channelsService->formatToChannellistCount($channels);
        });

        return $this->json(
            [
                "status" => "OK",
                "result" => $channellistCount
            ],
            Response::HTTP_OK
        );
    }
}
