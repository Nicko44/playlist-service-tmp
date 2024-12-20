<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=RuleRepository::Class)
 */
class Rule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     * @Groups("channels")
     * @Groups("rules")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channels")
     * @Groups("rules")
     */
    private ?string $policy;

    /**
     * @ORM\Column(type="array")
     * @OA\Property(
     *   type="array", @OA\Items(type="string")
     * )
     * @Groups("channels")
     * @Groups("rules")
     */
    private array $countries = [];

    /**
     * @ORM\Column(type="array")
     * @OA\Property(
     *   type="array", @OA\Items(type="string")
     * )
     * @Groups("channels")
     * @Groups("rules")
     */
    private array $platforms = [];

    /**
     * @ORM\Column(type="array")
     * @OA\Property(
     *   type="array", @OA\Items(type="string")
     * )
     * @Groups("channels")
     * @Groups("rules")
     */
    private array $userGroups = [];

    /**
     * @ORM\ManyToOne(targetEntity=Channel::Class, inversedBy="rules")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Groups("rules")
     */
    private ?Channel $channel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    public function setPolicy(string $policy): self
    {
        $this->policy = $policy;
        return $this;
    }

    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function setCountries(array $countries): self
    {
        $this->countries = $countries;
        return $this;
    }
    
    public function getPlatforms(): ?array
    {
        return $this->platforms;
    }

    public function setPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;
        return $this;
    }

    public function getUserGroups(): ?array
    {
        return $this->userGroups;
    }

    public function setUserGroups(array $userGroups): self
    {
        $this->userGroups = $userGroups;
        return $this;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }
}

