<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\JsonRPCEndpointBundle\Service\JsonRpcEndpoint as SDKJsonRpcEndpoint;

class JsonRpcController extends AbstractController
{
    public const LEGACY_ROUTE_NAME = 'json_rpc_http_api_endpoint_legacy';

    public const GET_METHOD_ROUTE_NAME = 'json_rpc_http_server_endpoint_get';

    public function __construct(private readonly SDKJsonRpcEndpoint $sdkEndpoint)
    {
    }

    #[Route(path: '/json-rpc', methods: ['OPTIONS'])]
    public function httpOptions(string $type): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', "application/{$type}");

        // Set allowed http methods
        $allowedMethodListString = implode(', ', [Request::METHOD_POST, Request::METHOD_OPTIONS]);
        $response->headers->set('Allow', $allowedMethodListString);
        $response->headers->set('Access-Control-Request-Method', $allowedMethodListString);

        // Set allowed content type
        $response->headers->set('Accept', "application/{$type}");
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    #[Route(path: '/server/json-rpc', name: 'json_rpc_http_server_endpoint__legacy-1', methods: ['POST'])]
    #[Route(path: '/json-rpc', name: 'json_rpc_http_server_endpoint', methods: ['POST'])]
    public function httpPost(Request $request): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $response->setContent($this->sdkEndpoint->index($request->getContent(), $request));

        return $response;
    }

    #[Route(path: '/api/json-rpc', name: self::LEGACY_ROUTE_NAME, methods: ['GET'])]
    #[Route(path: '/json-rpc', name: self::GET_METHOD_ROUTE_NAME, methods: ['GET'])]
    public function httpGet(Request $request): Response
    {
        $response = new Response();

        $content = $this->sdkEndpoint->index($request->query->get('__payload', ''), $request);

        // JSONP支持
        $callback = $request->query->get('callback', '');
        if (!empty($callback) && \JsonpCallbackValidator::validate($callback)) {
            $response->headers->set('Content-Type', 'application/javascript');
            $content = "{$callback}({$content})";
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }

        $response->setContent($content);

        return $response;
    }
}
