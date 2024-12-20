<?php

namespace App\Controller;

use App\Entity\Logo;
use App\Repository\LogoRepository;
use App\Repository\ChannelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class LogosController extends AbstractController
{
    public function __construct(
        private LogoRepository $logoRepository,
        private ChannelRepository $channelRepository,
        )
    {
        $this->logoRepository = $logoRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * @Route("v1/logos", name="getAllLogos", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns all Logos",
     *     @Model(type=Logo::class, groups={"logos"})
     * )
     * @OA\Tag(name="logos")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getAllLogos(): Response
    {
        $logos = $this->logoRepository->findAll();
        
        return $this->json(
            [
                "status" => "OK",
                "result" => $logos
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'logos']
        );
    }

    /**
     * @Route("v1/channel/{channelId}/logos", name="getLogos", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Logos by Channel ID",
     *     @Model(type=Logo::class, groups={"logos"})
     * )
     * @OA\Tag(name="logos")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getLogos($channelId): Response
    {
        $logos = $this->logoRepository->findBy(['channel' => $channelId]);

        if (empty($logos)){
            throw new HttpException(404, "not found");
        }

        return $this->json(
            [
                "status" => "OK",
                "result" => $logos
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'logos']
        );
    }

    /**
     * @Route("v1/channel/{channelId}/logos", name="createLogo", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Create Logo by Channel ID",
     *     @Model(type=Logo::class, groups={"logos"})
     * )
     * @OA\Parameter(
     *     name="Logo",
     *     in="query",
     *     description="The fields used to create Logo by Channel ID",
     *     @Model(type=Logo::class, groups={"logos"})
     * )
     * @OA\Tag(name="logos")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function createLogo($channelId, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $channel = $this->channelRepository->findOneBy(["id" => $channelId]);

        if($channel == null){
            throw new HttpException(400, "channel does not exist");
        }        

        $newLogo = new Logo;

        $newLogo->setName($data['name'])
                    ->setChannel($channel)
                    ->setBackground($data['background'])
                    ->setType($data['type']);

        $this->logoRepository->save($newLogo, true);
        $logos = $this->logoRepository->findBy(['channel' => $channelId]);

        if(empty($logos)){
            throw new HttpException(404, "not found");
        }
        
        return $this->json(
            [
                "status" => "OK",
                "result" => $logos
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'logos']
        );
    }

    /**
     * @Route("v1/channel/{channelId}/logos", name="deleteAllLogos", methods={"DELETE"})
     * @OA\Response(
     *     response=204,
     *     description="Delete Logo by Channel ID"
     * )
     * @OA\Tag(name="logos")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function deleteAllLogos($channelId): Response
    {
        $logos = $this->logoRepository->findBy(['channel' => $channelId]);

        if(empty($logos)){
            throw new HttpException(404, "not found");
        }

        $this->logoRepository->removeBy(['channel' => $channelId]);

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT,
        );
    }
}
