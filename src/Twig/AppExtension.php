<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
            new TwigFilter('render_website_data', [$this, 'renderWebsiteData']),
        ];
    }

    public function jsonDecode($data)
    {
        return json_decode($data, true);
    }

    public function renderWebsiteData($data)
    {
        return json_encode(json_decode($data), JSON_PRETTY_PRINT);
    }
}
