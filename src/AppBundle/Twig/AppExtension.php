<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
        new \Twig_SimpleFilter('json_format', [$this, 'formatJson']),
        ];
    }

    public function formatJson($value, $options = 0)
    {
        $json = json_decode($value);

        return json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function getName()
    {
        return 'app_extension';
    }
}
