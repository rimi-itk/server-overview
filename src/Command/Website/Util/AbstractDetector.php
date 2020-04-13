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

    public function getType(): string
    {
        return $this->type;
    }

    public function getCommand(Website $website): string
    {
        if (null === $this->command) {
            throw new RuntimeException('command is not defined in '.static::class);
        }

        return $this->command;
    }

    abstract public function getVersion(string $output, Website $website): ?string;

    protected function parseJson(string $json): ?array
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return null;
        }
    }
}
