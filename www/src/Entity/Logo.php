<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=LogoRepository::Class)
 */
class Logo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     * @Groups("channels")
     * @Groups("logos")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("logos")
     */
    private ?string $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("logos")
     */
    private ?string $background;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("channellist")
     * @Groups("channels")
     * @Groups("logos")
     */
    private ?string $type;

    /**
     * @ORM\ManyToOne(targetEntity=Channel::Class, inversedBy="logos")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Groups("logos")
     */
    private ?Channel $channel;

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

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(string $background): self
    {
        $this->background = $background;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
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




