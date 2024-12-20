<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Service\ChannelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ChannelsController extends AbstractController
{
    public function __construct(
        private readonly ChannelRepository  $channelRepository,
        private readonly ChannelsService    $channelsService,
        private readonly CategoryRepository $categoryRepository,
    )
    {
    }

    /**
     * @Route("v1/channels", name="getAllChannels", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns all Channels",
     *     @Model(type=Channel::class, groups={"channels"})
     * )
     * @OA\Tag(name="channels")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getAllChannels(): Response
    {
        $channels = $this->channelRepository->findAll();
        $this->channelsService->sortByCategoryAndChannelSequence($channels);

        return $this->json(
            [
                "status" => "OK",
                "result" => $channels
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channels']
        );
    }

    /**
     * @Route("v1/channels/{channelId}", name="getChannel", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns Channel by ID",
     *     @Model(type=Channel::class, groups={"channels"})
     * )
     * @OA\Tag(name="channels")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function getChannel($channelId): Response
    {
        $channel = $this->channelRepository->findOneBy(['id' => $channelId]);
        if (is_null($channel)) {
            throw new HttpException(404, "not found");
        }

        return $this->json(
            [
                "status" => "OK",
                "result" => $channel
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channels']
        );
    }

    /**
     * @Route("v1/channels/{channelId}", name="createChannel", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Create Channel by ID",
     *     @Model(type=Channel::class, groups={"channels"})
     * )
     * @OA\Parameter(
     *     name="Channel",
     *     in="query",
     *     description="The fields used to create Channel by ID",
     *     @Model(type=Channel::class, groups={"channels-input"})
     * )
     * @OA\Tag(name="channels")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function createChannel($channelId, Request $request): Response
    {
        $data = json_decode($request->getContent());

        $categoryId = $data->category->id;
        $category = $this->categoryRepository->findOneBy(['id' => $categoryId]);

        if ($category == null) {
            throw new HttpException(400, "category does not exist");
        }

        $trustedResolutions = explode(',', $this->getParameter('TRUSTED_RESOLUTIONS'));

        if (0 != count(array_diff($data->resolutions, $trustedResolutions))) {
            throw new HttpException(400, "bad resolutions value");
        }

        $maxCountInCategory = count($this->channelRepository->findAllOrderBySequence($categoryId)) + 1;

        if ($data->sequence > $maxCountInCategory) {
            throw new HttpException(400, "max sequence in $categoryId category is $maxCountInCategory");
        }

        $this->channelsService->increaseChannelSequence($data->sequence, 1, $categoryId);

        $newChannel = new Channel;

        $newChannel->setId($channelId)
            ->setName($data->name)
            ->setSortCountries($data->sortCountries ?? array())
            ->setSequence($data->sequence)
            ->setCategory($category)
            ->setResolutions($data->resolutions)
            ->setCoder($data->coder)
            ->setStorage($data->storage)
            ->setArchive($data->archive);

        $this->channelRepository->save($newChannel, true);

        return $this->json(
            [
                "status" => "OK",
                "result" => $newChannel
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'channels']
        );
    }

    /**
     * @Route("v1/channels/{channelId}", name="updateChannel", methods={"PATCH"})
     * @OA\Response(
     *     response=200,
     *     description="Change Channel by ID",
     *     @Model(type=Channel::class, groups={"channels"})
     * )
     * @OA\Parameter(
     *     name="Channel",
     *     in="query",
     *     description="The fields used to change Channel by ID",
     *     @Model(type=Channel::class, groups={"channels-input"})
     * )
     * @OA\Tag(name="channels")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function updateChannel($channelId, Request $request): Response
    {
        $channel = $this->channelRepository->findOneBy(['id' => $channelId]);
        $data = json_decode($request->getContent());

        $newCategoryId = 0;
        $oldCategoryId = $channel->getCategory()->getId();

        if (isset($data->category->id)) {
            $newCategoryId = $data->category->id;
            $newCategory = $this->categoryRepository->findOneBy(['id' => $newCategoryId]);
            if ($newCategory == null) {
                throw new HttpException(400, "category does not exist");
            }

            // якщо категорія змінюється, а послідовність всередені категорії задана, то змінюється як категорія так і послідовність всередені нової категорії
            if ($data->category->id != $channel->getCategory()->getId() && isset($data->sequence)) {
                $maxCountInNewCategory = count($this->channelRepository->findAllOrderBySequence($newCategoryId)) + 1;
                $newSequence = $data->sequence;

                if ($newSequence >= $maxCountInNewCategory) {
                    throw new HttpException(400, "max sequence in $newCategoryId category is $maxCountInNewCategory");
                }

                $this->channelsService->increaseChannelSequence($newSequence, 1, $newCategoryId);

                $channel->setSequence($newSequence);
                $channel->setCategory($newCategory);
            }

            // якщо категорія змінюється та послідовність всередені категорії НЕ задана, то додаємо канал останньою в послідовності нової категорії
            if ($data->category->id != $channel->getCategory()->getId() && !isset($data->sequence)) {
                $maxCountInNewCategory = count($this->channelRepository->findAllOrderBySequence($newCategoryId)) + 1;
                $newSequence = $maxCountInNewCategory + 1;

                $channel->setSequence($newSequence);
                $channel->setCategory($newCategory);
            }

            // якщо категорія НЕ змінюється, а послідовність всередені категорії задана, то змінюється лише послідовність всередені категорії
            if ($data->category->id == $channel->getCategory()->getId() && isset($data->sequence)) {
                $maxCountInOldCategory = count($this->channelRepository->findAllOrderBySequence($oldCategoryId));
                $maxCountInOldCategory++;
                $newSequence = $data->sequence;
    
                if ($newSequence >= $maxCountInOldCategory) {
                    throw new HttpException(400, "max sequence in $oldCategoryId category is $maxCountInOldCategory");
                }

                $this->channelsService->increaseChannelSequence($newSequence, 1, $oldCategoryId);
    
                $channel->setSequence($newSequence);
            }
        } else {
            // якщо категорія НЕ вказана, а послідовність всередені категорії задана, то змінюється лише послідовність всередені категорії
            if (isset($data->sequence)) {
                $maxCountInOldCategory = count($this->channelRepository->findAllOrderBySequence($oldCategoryId));
                $maxCountInOldCategory++;
                $newSequence = $data->sequence;
    
                if ($newSequence >= $maxCountInOldCategory) {
                    throw new HttpException(400, "max sequence in $oldCategoryId category is $maxCountInOldCategory");
                }

                $this->channelsService->increaseChannelSequence($newSequence, 1, $oldCategoryId);
    
                $channel->setSequence($newSequence);
            }
        }

        // якщо категорія НЕ змінюється, а послідовність всередені категорії НЕ задана, канал залишається на своєму місці

        if (isset($data->resolutions)) {
            $trustedResolutions = explode(',', $this->getParameter('TRUSTED_RESOLUTIONS'));

            if (0 != count(array_diff($data->resolutions, $trustedResolutions))) {
                throw new HttpException(400, "bad resolutions value");
            }
        }


        if (isset($data->id)) $channel->setId($data->id);
        if (isset($data->sortCountries)) $channel->setSortCountries($data->sortCountries);
        if (isset($data->name)) $channel->setName($data->name);
        if (isset($data->coder)) $channel->setCoder($data->coder);
        if (isset($data->resolutions)) $channel->setResolutions($data->resolutions);
        if (isset($data->storage)) $channel->setStorage($data->storage);
        if (isset($data->archive)) $channel->setArchive($data->archive);

        $this->channelRepository->save($channel, true);

        // перебудовуємо послідовність старої/нової категорії якщо в них були якісь переміщення каналів
        $this->channelsService->resetChannelSequence($oldCategoryId);
        if ($newCategoryId !== 0) $this->channelsService->resetChannelSequence($newCategoryId);

        return $this->json(
            [
                "status" => "OK",
                "result" => $channel
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'channels']
        );
    }

    /**
     * @Route("v1/channels/{channelId}", name="deleteChannel", methods={"DELETE"})
     * @OA\Response(
     *     response=204,
     *     description="Delete Channel by ID"
     * )
     * @OA\Tag(name="channels")
     * @Security(name="X-AUTH-TOKEN")
     */
    public function deleteChannel($channelId): Response
    {
        $channel = $this->channelRepository->findOneBy(['id' => $channelId]);
        if (is_null($channel)) {
            throw new HttpException(404, "not found");
        }

        $this->channelRepository->remove($channel, true);
        $this->channelsService->resetChannelSequence($channel->getCategory());

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT
        );
    }
}
