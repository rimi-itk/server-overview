<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JsonSerializable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 * @UniqueEntity("name")
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"read"}}
 * )
 */
class Server implements JsonSerializable
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups("read")
     */
    private $name;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $data;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Website", mappedBy="server")
     */
    private $websites;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("read")
     */
    private $search;

    public function __construct()
    {
        $this->websites = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName() ?? self::class;
    }

    public function getId()
    {
        return $this->id;
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

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites(): Collection
    {
        return $this->websites;
    }

    public function addWebsite(Website $website): self
    {
        if (!$this->websites->contains($website)) {
            $this->websites[] = $website;
            $website->setServer($this);
        }

        return $this;
    }

    public function removeWebsite(Website $website): self
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
            // set the owning side to null (unless already changed)
            if ($website->getServer() === $this) {
                $website->setServer(null);
            }
        }

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * @param mixed $search
     *
     * @return Server
     */
    public function setSearch(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
