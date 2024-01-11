<?php

declare(strict_types=1);

namespace Los\Cors;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_merge;
use function is_array;
use function is_string;

final class CorsMiddleware implements MiddlewareInterface
{
    /** @phpstan-ignore-next-line */
    private array $options = [];

     /**
      * @var array{
      *  origin_server: string,
      *  allowed_origins: array<string>,
      *  allowed_methods: array<string>,
      *  allowed_headers: array<string>,
      *  exposed_headers: array<string>,
      *  allowed_credentials: bool,
      *  enable_check_host: bool,
      *  cache_max_age: int,
      * }
      */
    private array $defaultOptions = [
        'allowed_origins'     => [],
        'allowed_methods'     => [],
        'allowed_headers'     => [],
        'exposed_headers'     => [],
        'allowed_credentials' => false,
        'cache_max_age'       => 0,
        'origin_server'       => '',
        'enable_check_host'   => false,
    ];

    /** @phpstan-ignore-next-line */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cors = Analyzer::instance($this->createCorsSettings($request))->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return (new Response())->withStatus(403);

            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $handler->handle($request);

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $corsHeaders = $cors->getResponseHeaders();
                $response    = new Response();
                foreach ($corsHeaders as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (! is_array($value)) {
                        $value = (string) $value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response->withStatus(200);

            default:
                $response = $handler->handle($request);

                $corsHeaders = $cors->getResponseHeaders();
                foreach ($corsHeaders as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (! is_array($value)) {
                        $value = (string) $value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response;
        }
    }

    private function createCorsSettings(ServerRequestInterface $request): AnalysisStrategyInterface
    {
        $server = $this->serverOrigin($request->getUri());

        $settings = new Settings();
        $settings->init($server->getScheme(), $server->getHost(), $server->getPort() ?? 80)
            ->setExposedHeaders($this->options["exposed_headers"])
            ->setPreFlightCacheMaxAge($this->options["cache_max_age"])
            ->enableAllOriginsAllowed()
            ->enableAllMethodsAllowed()
            ->enableAllHeadersAllowed();

        if (! empty($this->options["allowed_origins"])) {
            $settings->setAllowedOrigins($this->options["allowed_origins"]);
        }

        if (! empty($this->options["allowed_methods"])) {
            $settings->setAllowedMethods($this->options["allowed_methods"]);
        }

        if (! empty($this->options["allowed_headers"])) {
            $settings->setAllowedHeaders($this->options["allowed_headers"]);
        }

        if ($this->options["allowed_credentials"]) {
            $settings->setCredentialsSupported();
        }

        if ($this->options['enable_check_host']) {
            $settings->enableCheckHost();
        }

        return $settings;
    }

    private function serverOrigin(UriInterface $server): UriInterface
    {
        if (! is_string($this->options['origin_server']) || empty($this->options['origin_server'])) {
            return $server;
        }

        return new Uri($this->options['origin_server']);
    }
}
