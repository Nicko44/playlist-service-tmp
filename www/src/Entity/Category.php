<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;


/**
 * @ORM\Entity(repositoryClass=CategoryRepository::Class)
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("channels-input")
     * @Groups("categories")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="json")
     * @OA\Property(
     *   property="name",
     *   type="string"
     * )
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("categories")
     */
    private ?array $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups("channels")
     * @Groups("categories")
     */
    private ?int $sequence;

    /**
     * @ORM\OneToMany(
     *   targetEntity=Channel::Class,
     *   mappedBy="category"
     * )
     */
    private Collection $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
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

    public function getName(): ?array
    {
        return $this->name;
    }

    public function setName($name): self
    {
        $this->name = $name;
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

    /**
     * @return Collection<int, Channel>
     */
    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(Channel $channel): self
    {
        if (!$this->channels->contains($channel)) {
            $this->channels->add($channel);
            $channel->setCategory($this);
        }

        return $this;
    }

    public function removeChannel(Channel $channel): self
    {
        if ($this->channels->removeElement($channel)) {
            // set the owning side to null (unless already changed)
            if ($channel->getCategory() === $this) {
                $channel->setCategory(null);
            }
        }

        return $this;
    }
}
