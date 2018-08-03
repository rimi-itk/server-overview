<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Website.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\WebsiteRepository")
 * @Gedmo\Loggable;
 */
class Website
{
    use TimestampableEntity;

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
     */
    private $domain;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Server", inversedBy="websites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $server;

    /**
     * @var string
     *
     * @ORM\Column(name="document_root", type="string", length=255, nullable=true)
     */
    private $documentRoot;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255, nullable=true)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comments;

    public function __toString()
    {
        return json_encode([
        $this->getDomain(),
        $this->getDocumentRoot(),
        $this->getType(),
        $this->getVersion(),
        ], JSON_UNESCAPED_SLASHES);

        return __CLASS__;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set domain.
     *
     * @param string $domain
     *
     * @return Website
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set server.
     *
     * @param Server $server
     *
     * @return Website
     */
    public function setServer(Server $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server.
     *
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set documentRoot.
     *
     * @param string $documentRoot
     *
     * @return Website
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;

        return $this;
    }

    /**
     * Get documentRoot.
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Website
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return Website
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set data.
     *
     * @param string $data
     *
     * @return Website
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     *
     * @return Website
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }
}
