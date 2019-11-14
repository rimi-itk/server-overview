<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website\Util;

use App\Entity\Website;

abstract class AbstractDetector
{
    /**
     * Website type. On of the Website::TYPE_* constants.
     *
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $command;

    /**
     * AbstractDetector constructor.
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCommand(Website $website)
    {
        if (null === $this->command) {
            throw new \RuntimeException('command is not defined in '.static::class);
        }

        return $this->command;
    }

    /**
     * @return string
     */
    abstract public function getVersion(string $output, Website $website);
}
