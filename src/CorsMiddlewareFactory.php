<?php

declare(strict_types=1);

namespace Los\Cors;

use Neomerx\Cors\Strategies\Settings;
use Psr\Container\ContainerInterface;

use function array_change_key_case;
use function array_fill_keys;
use function array_merge;
use function assert;
use function is_int;

use const CASE_LOWER;

class CorsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CorsMiddleware
    {
        $config     = $container->get('config');
        $corsConfig = array_merge([
            'allowed_origins'     => ['*'],
            'allowed_methods'     => ['GET', 'OPTIONS'],
            'allowed_headers'     => ['Authorization', 'Accept', 'Content-Type'],
            'expose_headers'      => [],
            'allowed_credentials' => false,
            'max_age'             => 120,
        ], $config['los']['cors'] ?? $config['los-cors'] ?? []);

        assert(is_int($corsConfig['max_age']));

        $settings = new Settings();

        $origin = array_fill_keys($corsConfig['allowed_origins'], true);
        $settings->setRequestAllowedOrigins($origin);

        $methods = array_fill_keys($corsConfig['allowed_methods'], true);
        $settings->setRequestAllowedMethods($methods);

        $headers = array_fill_keys($corsConfig['allowed_headers'], true);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $settings->setRequestAllowedHeaders($headers);

        $headers = array_fill_keys($corsConfig['expose_headers'], true);
        $settings->setResponseExposedHeaders($headers);

        $settings->setRequestCredentialsSupported((bool) $corsConfig['credentials']);
        $settings->setPreFlightCacheMaxAge($corsConfig['max_age']);

        return new CorsMiddleware($settings);
    }
}
