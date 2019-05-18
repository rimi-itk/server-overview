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

abstract class AbstractDataProvider
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $command;

    public function __construct()
    {
        if (null === $this->key) {
            throw new \RuntimeException('key is not defined in '.static::class);
        }
    }

    /**
     * @param Website $website
     *
     * @return bool
     */
    abstract public function canHandle(Website $website);

    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param Website $website
     *
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
     * @param string  $output
     * @param Website $website
     *
     * @return null|array
     */
    abstract public function getData(string $output, Website $website);
}
