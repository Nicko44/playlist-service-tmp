<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;

class SearchController extends AbstractController
{
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * @Route("/v2/search", name="search", methods={"GET"})
     * @OA\Response(
     *   response=200,
     *   description="Returns text/plain 'pong'",
     * )
     * @OA\Tag(name="search")
     * @Security(name="Authorization")
     */
    public function search(Request $request): Response
    {
        $user = $this->userService->getUserData($request->headers->get('Authorization'));

        return new Response("pong", Response::HTTP_OK);
    }
}
