<?php
namespace LosMiddleware\LosCors;

use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CorsMiddleware
{
    private $settings;

    public function __construct(SettingsStrategyInterface $settings = null)
    {
        $this->settings = $settings ?: new Settings();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param null|callable $next
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        $cors = Analyzer::instance($this->settings)->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $response->withStatus(403);

            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $next($request, $response);

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $corsHeaders = $cors->getResponseHeaders();
                foreach ($corsHeaders as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (!is_array($value)) {
                        $value = (string)$value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response->withStatus(200);

            default:
                $response = $next($request, $response);

                $corsHeaders = $cors->getResponseHeaders();
                foreach ($corsHeaders as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (!is_array($value)) {
                        $value = (string)$value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response;
        }
    }
}
