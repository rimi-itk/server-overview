<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
        new \Twig_SimpleFilter('json_format', [ $this, 'formatJson' ]),
        );
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
