<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Twig;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
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

    public function getFunctions()
    {
        return [
            new TwigFunction('current_path', [$this, 'currentPath'], ['is_safe' => ['all']]),
            new TwigFunction('path_with_referer', [$this, 'pathWithReferer'], ['is_safe' => ['all']]),
        ];
    }

    public function currentPath(array $params = []): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new RuntimeException('Cannot get current request');
        }

        return $this->router->generate(
            $request->attributes->get('_route'),
            array_replace_recursive($request->attributes->get('_route_params'), $request->query->all(), $params)
        );
    }

    public function pathWithReferer(string $route, array $params = []): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new RuntimeException('Cannot get current request');
        }

        return $this->router->generate(
            $route,
            array_replace_recursive([
                'referer' => $this->currentPath(),
            ], $params)
        );
    }
}
