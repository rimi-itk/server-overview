<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Filter\RegexpFilter;
use App\Repository\WebsiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Website.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\WebsiteRepository")
 * @Gedmo\Loggable;
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"read"}}
 * )
 * @ApiFilter(SearchFilter::class, properties={"domain", "server.name", "type", "version", "data": "partial", "search": "partial"})
 * @ApiFilter(RegexpFilter::class, properties={"domain", "server.name", "type", "version", "data", "search"})
 */
class Website
{
    use TimestampableEntity;

    public const TYPE_DRUPAL = 'drupal';
    public const TYPE_DRUPAL_MULTISITE = 'drupal (multisite)';
    public const TYPE_PROXY = 'proxy';
    public const TYPE_SYMFONY = 'symfony';
    public const TYPE_UNKNOWN = '🐼'; // Panda face
    public const VERSION_UNKNOWN = '👻'; // Ghost
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=false)
     *
     * @Groups("read")
     */
    private $domain;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Server", inversedBy="websites")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups("read")
     */
    private $server;

    /**
     * @var string
     *
     * @ORM\Column(name="document_root", type="string", length=255, nullable=true)
     *
     * @Groups("read")
     */
    private $documentRoot;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     *
     * @Groups("read")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255, nullable=true)
     *
     * @Groups("read")
     */
    private $version;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="json", nullable=true)
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comments;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $errors;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $updates;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siteRoot;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("read")
     */
    private $search;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Audience")
     */
    private $audiences;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    public function __construct()
    {
        $this->audiences = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getDomain() ?? self::class;
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set domain.
     *
     * @param string $domain
     */
    public function setDomain($domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain.
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set server.
     */
    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server.
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Get server name.
     */
    public function getServerName(): ?string
    {
        return $this->getServer()->getName();
    }

    /**
     * Set documentRoot.
     *
     * @param string $documentRoot
     */
    public function setDocumentRoot($documentRoot): self
    {
        $this->documentRoot = $documentRoot;

        return $this;
    }

    /**
     * Get documentRoot.
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    public function getProjectDir(): string
    {
        if (self::TYPE_SYMFONY === $this->getType()) {
            return \dirname($this->getDocumentRoot());
        }

        return $this->getDocumentRoot();
    }

    /**
     * Set type.
     *
     * @param string $type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set version.
     *
     * @param string $version
     */
    public function setVersion($version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set data.
     */
    public function setData(array $data = null): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     */
    public function getData(): ?array
    {
        return $this->data ?? [];
    }

    public function addData(array $data): self
    {
        return $this->setData(array_merge($this->getData(), $data));
    }

    /**
     * Set comments.
     *
     * @param string $comments
     */
    public function setComments($comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getErrors(): ?string
    {
        return $this->errors;
    }

    public function setErrors(?string $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function getUpdates(): ?string
    {
        return $this->updates;
    }

    public function setUpdates(?string $updates): self
    {
        $this->updates = $updates;

        return $this;
    }

    public function getSiteRoot(): ?string
    {
        return $this->siteRoot;
    }

    public function setSiteRoot(?string $siteRoot): self
    {
        $this->siteRoot = $siteRoot;

        return $this;
    }

    public static function getValuesList($property): array
    {
        return WebsiteRepository::getValuesList($property);
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * @return Audience[]|Collection
     */
    public function getAudiences(): Collection
    {
        return $this->audiences;
    }

    public function addAudience(Audience $audience): self
    {
        if (!$this->audiences->contains($audience)) {
            $this->audiences[] = $audience;
        }

        return $this;
    }

    public function removeAudience(Audience $audience): self
    {
        if ($this->audiences->contains($audience)) {
            $this->audiences->removeElement($audience);
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
}
