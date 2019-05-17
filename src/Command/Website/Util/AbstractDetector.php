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

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCommand(Website $website)
    {
        if (null === $this->command) {
            throw new \RuntimeException('command is not defined in '.self::class);
        }

        return $this->command;
    }

    abstract public function getVersion(string $output, Website $website);
}
