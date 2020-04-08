<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var RouterInterface */
    private $router;

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
            new TwigFilter('render_website_data', [$this, 'renderWebsiteData']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('current_path', [$this, 'currentPath'], ['is_safe' => ['all']]),
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

    public function currentPath(array $params = []): string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->router->generate(
            $request->attributes->get('_route'),
            array_merge($request->attributes->get('_route_params'), $request->query->all(), $params)
        );
    }
}
