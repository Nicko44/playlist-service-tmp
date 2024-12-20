<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=ChannelRepository::Class)
 */
class Channel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("logos")
     * @Groups("rules")
     * @Groups("access")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?string $name;

    /**
     * @ORM\Column(type="array")
     * @OA\Property(
     *   type="array",
     *   @OA\Items(type="string")
     * )
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private array $sortCountries = [];

    /**
     * @ORM\Column(type="integer")
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?int $sequence;

    /**
     * @ORM\ManyToOne(targetEntity=Category::Class, inversedBy="channels")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?Category $category;

    /**
     * @ORM\Column(type="array")
     * @OA\Property(
     *   type="array",
     *   @OA\Items(type="string")
     * )
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private array $resolutions = [];

    /**
     * @ORM\Column(type="integer")
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?int $archive;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?string $coder;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channels")
     * @Groups("channels-input")
     */
    private ?string $storage;

    /**
     * @ORM\OneToMany(targetEntity=Logo::Class, mappedBy="channel")
     * @Groups("channellist")
     * @Groups("channels")
     */
    private Collection $logos;

    /**
     * @ORM\OneToMany(targetEntity=Rule::Class, mappedBy="channel")
     * @Groups("channels")
     */
    private Collection $rules;

    public function __construct()
    {
        $this->logos = new ArrayCollection();
        $this->rules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSortCountries(): ?array
    {
        return $this->sortCountries;
    }

    public function setSortCountries(array $sortCountries): self
    {
        $this->sortCountries = $sortCountries;
        return $this;
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getResolutions(): ?array
    {
        return $this->resolutions;
    }

    public function setResolutions(array $resolutions): self
    {
        $this->resolutions = $resolutions;
        return $this;
    }

    public function getArchive(): ?int
    {
        return $this->archive;
    }

    public function setArchive(int $archive): self
    {
        $this->archive = $archive;
        return $this;
    }

    public function getCoder(): ?string
    {
        return $this->coder;
    }

    public function setCoder(string $coder): self
    {
        $this->coder = $coder;
        return $this;
    }

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function setStorage(string $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return Collection<int, Logo>
     */
    public function getLogos(): Collection
    {
        return $this->logos;
    }

    /**
     * @return Collection<int, Rule>
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addLogo(Logo $logo): self
    {
        if (!$this->logos->contains($logo)) {
            $this->logos->add($logo);
            $logo->setChannel($this);
        }

        return $this;
    }

    public function removeLogo(Logo $logo): self
    {
        if ($this->logos->removeElement($logo)) {
            // set the owning side to null (unless already changed)
            if ($logo->getChannel() === $this) {
                $logo->setChannel(null);
            }
        }

        return $this;
    }

    public function addRule(Rule $rule): self
    {
        if (!$this->rules->contains($rule)) {
            $this->rules->add($rule);
            $rule->setChannel($this);
        }

        return $this;
    }

    public function removeRule(Rule $rule): self
    {
        if ($this->rules->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getChannel() === $this) {
                $rule->setChannel(null);
            }
        }

        return $this;
    }
}
