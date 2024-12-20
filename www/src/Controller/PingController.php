<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class PingController extends AbstractController
{
    /**
    * @Route("/ping", name="ping", methods={"GET"})
    * @OA\Response(
    *   response=200,
    *   description="Returns text/plain 'pong'",
    * )
    */
    public function ping(): Response
    {
        return new Response("pong", Response::HTTP_OK);
    }
}
