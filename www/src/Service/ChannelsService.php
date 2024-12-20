<?php

namespace App\Service;

use Exception;
use App\Entity\Channel;
use App\Repository\ChannelRepository;
use App\Repository\RuleRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChannelsService
{
    public function __construct(
        private readonly ChannelRepository $channelRepository,
        private readonly RuleRepository    $ruleRepository,
        private readonly ParameterBagInterface $params
        )
    {
    }

    private function sortByCountry($channels, $country): array
    {
        $filteredArr = array_filter($channels, function($channel) use ($country) {
            return in_array($country, $channel->getSortCountries());
        });
        if(count($filteredArr) !== 0){
            usort($filteredArr, function(Channel $first,Channel $second): int {
                return $first->getSequence() <=> $second->getSequence();
            });
        }

        $unfilteredArr = array_filter($channels, function($channel) use ($country) {
            return !in_array($country, $channel->getSortCountries());
        });
        if(count($unfilteredArr) !== 0){
            usort($unfilteredArr, function(Channel $first,Channel $second): int {
                return $first->getSequence() <=> $second->getSequence();
            });
        }

        $arr = array_merge($filteredArr, $unfilteredArr);
        usort($arr, function(Channel $first,Channel $second): int {
            return $first->getCategory()->getSequence() <=> $second->getCategory()->getSequence();
        });
        return $arr;
    }

    public function sortByCategoryAndChannelSequence(&$channels): void
    {
        usort($channels, function(Channel $first,Channel $second): int {
            // ASC sort
            return [$first->getCategory()->getSequence(), $first->getSequence()]
                <=>
                [$second->getCategory()->getSequence(), $second->getSequence()];
        });
    }

    public function increaseChannelSequence(int $shiftStart, int $shiftDuration, int $categoryId): void
    {
        $channels = $this->channelRepository->findAllOrderBySequence($categoryId);

        foreach ($channels as $channel) {
            $seq = $channel->getSequence();

            if ($seq >= $shiftStart){
                $channel->setSequence($seq + $shiftDuration);
            }

            $this->channelRepository->save($channel, true);
        }
    }

    public function resetChannelSequence($categoryId): void
    {
        $channels = $this->channelRepository->findAllOrderBySequence($categoryId);
        $count = 1;

        foreach ($channels as $channel){
            if ($channel->getSequence() != $count){
                $channel->setSequence($count);
                $this->channelRepository->save($channel, true);
            }
            $count++;
        }
    }

    public function getChannelsByAccessRules($country, $platform, $userGroup, $adult): array
    {
        $channelsAll = $this->channelRepository->findAll();

        $channels = $this->sortByCountry($channelsAll, $country);

        $allowChannels = array();

        foreach($channels as $channel){
            $category = $channel->getCategory();

            // Dropping 18+ channel from channel set if this option DISABLE on billing
            if (!$adult && $category->getId() === 7){
                continue;
            }

            $rules = $this->ruleRepository->findBy(['channel' => $channel->getId()], ['id' => 'ASC']);
            if(empty($rules)){
                continue;
            }

            foreach($rules as $rule){
                $isInCountries = $this->isInArray($country, $rule->getCountries());
                $isInPlatforms = $this->isInArray($platform, $rule->getPlatforms());
                $isInUserGroups = $this->isInArray($userGroup, $rule->getUserGroups());

                if($isInCountries && $isInPlatforms && $isInUserGroups) {
                    switch ($rule->getPolicy()) {
                        case 'DENY':
                            $this->dropFromArray($allowChannels, $channel);
                            break;
                        case 'ALLOW':
                            $allowChannels[] = $channel;
                            break;
                    }
                }
            }
        }
        return array_values($allowChannels);
    }

    public function formatToChannellist(&$channels): void
    {
        foreach($channels as $channel){
            foreach($channel->getLogos() as $logo){
                $logo->setName($this->params->get('PREFIX_IMAGE_URL').$logo->getName());
            }
        }
    }

    public function formatToChannellistCount($channels): array
    {
        $total = 0;
        $sd = 0;
        $fullhd = 0;
        $fullhd50fps = 0;

        foreach($channels as $channel){
            $resolutions = $channel->getResolutions();

            if (in_array('sd', $resolutions)) $sd++;
            if (in_array('fullhd', $resolutions)) $fullhd++;
            if (in_array('fullhd50fps', $resolutions)) $fullhd50fps++;
            
            $total++;
        }

        return array(
            'total' => $total,
            'sd' => $sd,
            'fullhd' => $fullhd,
            'fullhd50fps' => $fullhd50fps,
        );
    }

    private function isInArray($item, $array): bool
    {
        if(empty($array)){
            return true;
        }      

        if(in_array($item, $array)){
            return true;
        }

        return false;
    }

    private function dropFromArray(&$array, $element): void
    {
        foreach ($array as $key => $item) {
            if($item->getId() == $element->getId()){
                unset($array[$key]);
            }
        }
    }
}
