<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class CategoriesController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    )
    {
    }

    /**
     * @Route("v1/categories", name="getAllCategories", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns all Categories",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Tag(name="categories")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getAllCategories(): Response
    {
        $categories = $this->categoryRepository->findAll();

        return $this->json(
            [
                "status" => "OK",
                "result" => $categories
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'categories']
        );
    }

    /**
     * @Route("v1/categories/{categoryId}", name="getCategory", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Category by ID",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Tag(name="categories")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getCategory($categoryId): Response
    {
        $category = $this->categoryRepository->findOneBy(['id' => $categoryId]);

        if (is_null($category)) {
            throw new HttpException(404, "not found");
        }

        return $this->json(
            [
                "status" => "OK",
                "result" => $category
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'categories']
        );
    }

    /**
     * @Route("v1/categories/{categoryId}", name="createCategory", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Create Category by ID",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Parameter(
     *     name="Category",
     *     in="query",
     *     description="The fields used to create Category by ID",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Tag(name="categories")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function createCategory($categoryId, Request $request): Response
    {
        $data = json_decode($request->getContent());

        $newCategory = new Category;

        $newCategory->setId($categoryId)
            ->setName((array)$data->name)
            ->setSequence($data->sequence);

        $this->categoryRepository->save($newCategory, true);

        return $this->json(
            [
                "status" => "OK",
                "result" => $newCategory
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'categories']
        );
    }

    /**
     * @Route("v1/categories/{categoryId}", name="updateCategory", methods={"PATCH"})
     * @OA\Response(
     *     response=200,
     *     description="Update Category by ID",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Parameter(
     *     name="Category",
     *     in="query",
     *     description="The fields used to create Category by ID",
     *     @Model(type=Category::class, groups={"categories"})
     * )
     * @OA\Tag(name="categories")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function updateCategory($categoryId, Request $request): Response
    {
        $data = json_decode($request->getContent());

        $category = $this->categoryRepository->findOneBy(['id' => $categoryId]);

        if (is_null($category)) {
            throw new HttpException(404, "not found");
        }

        if (!empty($data->name)) $category->setName((array)$data->name);
        // TODO make increaseSequence func
        if (!empty($data->sequence)) $category->setSequence($data->sequence);

        $this->categoryRepository->save($category, true);

        return $this->json(
            [
                "status" => "OK",
                "result" => $category
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'categories']
        );
    }

    /**
     * @Route("v1/categories/{categoryId}", name="deleteCategory", methods={"DELETE"})
     * @OA\Response(
     *     response=204,
     *     description="Delete Category by ID"
     * )
     * @OA\Tag(name="categories")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function deleteCategory($categoryId): Response
    {
        $category = $this->categoryRepository->findOneBy(['id' => $categoryId]);

        if (is_null($category)) {
            throw new HttpException(404, "not found");
        }

        $this->categoryRepository->remove($category, true);

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT,
        );
    }
}
