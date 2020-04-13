<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website\Util;

use App\Entity\Website;
use JsonException;
use RuntimeException;

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
            throw new RuntimeException('key is not defined in '.static::class);
        }
    }

    abstract public function canHandle(Website $website): bool;

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCommand(Website $website): string
    {
        if (null === $this->command) {
            throw new RuntimeException('command is not defined in '.static::class);
        }

        return $this->command;
    }

    abstract public function getData(string $output, Website $website): ?array;

    protected function parseJson(string $json): ?array
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return null;
        }
    }
}
