<?php

declare(strict_types=1);

namespace Los\Cors;

use Laminas\Diactoros\Response;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_array;

final class CorsMiddleware implements MiddlewareInterface
{
    private AnalysisStrategyInterface $settings;

    public function __construct(?AnalysisStrategyInterface $settings = null)
    {
        $this->settings = $settings ?: new Settings();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cors = Analyzer::instance($this->settings)->analyze($request);

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
}
