<?php

declare(strict_types=1);

namespace Los\Cors;

use Psr\Container\ContainerInterface;

class CorsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CorsMiddleware
    {
        $config = $container->get('config');

        return new CorsMiddleware($config['los']['cors'] ?? []);
    }
}
