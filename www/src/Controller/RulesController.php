<?php

namespace App\Controller;

use App\Entity\Rule;
use App\Repository\RuleRepository;
use App\Repository\ChannelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class RulesController extends AbstractController
{
    public function __construct(
        private readonly RuleRepository $ruleRepository,
        private readonly ChannelRepository $channelRepository,
        )
    {
    }

    /**
     * @Route("v1/rules", name="getAllRules", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns all Rules",
     *     @Model(type=Rule::class, groups={"rules"})
     * )
     * @OA\Tag(name="rules")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getAllRules(): Response
    {
        $rules = $this->ruleRepository->findAll();
        
        return $this->json(
            [
                "status" => "OK",
                "result" => $rules
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'rules']
        );
    }
    /**
     * @Route("v1/channel/{channelId}/rules", name="getRules", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Rules by Channel ID",
     *     @Model(type=Rule::class, groups={"rules"})
     * )
     * @OA\Tag(name="rules")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getRules($channelId): Response
    {
        $rules = $this->ruleRepository->findBy(['channel' => $channelId], ['id' => 'ASC']);

        if(empty($rules)){
            throw new HttpException(404, "not found");
        }
        
        return $this->json(
            [
                "status" => "OK",
                "result" => $rules
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'rules']
        );
    }

    /**
     * @Route("v1/channel/{channelId}/rules", name="createRules", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Create Rule by Channel ID",
     *     @Model(type=Rule::class, groups={"rules"})
     * )
     * @OA\Parameter(
     *     name="Rule",
     *     in="query",
     *     description="The fields used to create Rule by Channel ID",
     *     @Model(type=Rule::class, groups={"rules"})
     * )
     * @OA\Tag(name="rules")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function createRules($channelId, Request $request): Response{
        $data = json_decode($request->getContent(), true);

        $channel = $this->channelRepository->findOneBy(["id" => $channelId]);

        if($channel == null){
            throw new HttpException(400, "channel does not exist");
        }

        $countryList = array_map('strtoupper', $data['countries']);
        $platformList = array_map('strtolower', $data['platforms']);

        $newRule = new Rule;

        $newRule->setChannel($channel)
                    ->setPolicy($data['policy'])
                    ->setCountries($countryList)
                    ->setPlatforms($platformList)
                    ->setUserGroups($data['user_groups']);

        $this->ruleRepository->save($newRule, true);
        $rules = $this->ruleRepository->findBy(['channel' => $channelId], ['id' => 'ASC']);

        if(empty($rules)){
            throw new HttpException(404, "not found");
        }

        return $this->json(
            [
                "status" => "OK",
                "result" => $rules
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'rules']
        );
    }

    /**
     * @Route("v1/channel/{channelId}/rules", name="deleteAllRules", methods={"DELETE"})
     * @OA\Response(
     *     response=204,
     *     description="Delete Reles by Channel ID"
     * )
     * @OA\Tag(name="rules")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function deleteAllRules($channelId): Response
    {
        $rules = $this->ruleRepository->findBy(['channel' => $channelId], ['id' => 'ASC']);

        if(empty($rules)){
            throw new HttpException(404, "not found");
        }

        $this->ruleRepository->removeBy(['channel' => $channelId]);

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT,
        );
    }
    /**
     * @Route("v1/rules/{ruleId}", name="deleteRule", methods={"DELETE"})
     * @OA\Response(
     *     response=204,
     *     description="Delete Rele by Rule ID"
     * )
     * @OA\Tag(name="rules")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function deleteRule($ruleId): Response
    {
        $rule = $this->ruleRepository->findOneBy(['id' => $ruleId]);

        if(empty($rule)){
            throw new HttpException(404, "not found");
        }

        $this->ruleRepository->remove($rule, true);

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT,
        );
    }
}
