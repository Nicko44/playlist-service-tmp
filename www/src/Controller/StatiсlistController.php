<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Service\ChannelsService;
use App\Service\PlaylistService;
use App\Service\CategoriesService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class StatiсlistController extends AbstractController
{
    public function __construct(
        private ChannelsService $channelsService,
        private PlaylistService $playlistService,
        private UserService $userService,
        private CategoriesService $categoriesService,
        private CacheInterface $cache,
        private CategoryRepository $categoryRepository
    )
    {
    }

    /**
     * @Route("/v1/staticlist/{type}",
     *      name="getStaticPlaylist",
     *      methods={"GET"},
     *      requirements={"type"="ottplayer|dune"}
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns Static Playlist with default placeholder.<br>Allowed types:<br>- ottplayer<br>- dune"
     * )
     * @OA\Tag(name="staticlist")
     */
    public function getStaticPlaylist($type, Request $request): Response
    {
        $userGroup = 0;
        $adult = true;
        $platform = $this->getParameter('DEFAULT_PLATFORM');
        $language = $this->getParameter('DEFAULT_LANGUAGE');
        $country = $this->userService->getCountryCode($request);

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
        switch ($type){
            case 'ottplayer':
                $playlist = $this->playlistService->formatToOttplayer($channels, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'audio/mpegurl');
                $response->headers->set('Content-Disposition', 'attachment; filename=playlist.m3u');
                return $response;
            case 'dune':
                $categories = $this->categoryRepository->findAll();
                $this->categoriesService->sortBySequence($categories);
                $playlist = $this->playlistService->formatToDune($channels, $categories, $language);
                $response->setContent($playlist);
                $response->headers->set('Content-Type', 'application/xml');
                $response->headers->set('Content-Disposition', 'attachment; filename=nStream.xml');
                return $response;
        }
        return $response;
    }
}