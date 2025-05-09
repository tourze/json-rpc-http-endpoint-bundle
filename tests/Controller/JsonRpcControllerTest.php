<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPCEndpointBundle\Service\JsonRpcEndpoint as SDKJsonRpcEndpoint;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;

class JsonRpcControllerTest extends TestCase
{
    private SDKJsonRpcEndpoint $sdkEndpoint;
    private JsonRpcController $controller;

    protected function setUp(): void
    {
        $this->sdkEndpoint = $this->createMock(SDKJsonRpcEndpoint::class);
        $this->controller = new JsonRpcController($this->sdkEndpoint);
    }

    public function testHttpOptions_returnsResponseWithCorrectHeaders(): void
    {
        $response = $this->controller->httpOptions('json');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('POST, OPTIONS', $response->headers->get('Allow'));
        $this->assertEquals('POST, OPTIONS', $response->headers->get('Access-Control-Request-Method'));
        $this->assertEquals('application/json', $response->headers->get('Accept'));
        $this->assertEquals('Content-Type', $response->headers->get('Access-Control-Allow-Headers'));
    }

    public function testHttpPost_returnsJsonResponseWithContent(): void
    {
        $request = Request::create('/json-rpc', 'POST', [], [], [], [], '{"jsonrpc": "2.0", "method": "test", "id": 1}');
        $expectedContent = '{"jsonrpc": "2.0", "result": "success", "id": 1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with(
                $this->equalTo('{"jsonrpc": "2.0", "method": "test", "id": 1}'),
                $this->equalTo($request)
            )
            ->willReturn($expectedContent);

        $response = $this->controller->httpPost($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedContent, $response->getContent());
    }

    public function testHttpGet_withoutCallback_returnsJsonResponse(): void
    {
        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $request = Request::create('/json-rpc', 'GET', ['__payload' => $payload]);
        $expectedContent = '{"jsonrpc": "2.0", "result": "success", "id": 1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with(
                $this->equalTo($payload),
                $this->equalTo($request)
            )
            ->willReturn($expectedContent);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedContent, $response->getContent());
    }

    public function testHttpGet_withEmptyPayload_passesEmptyStringToEndpoint(): void
    {
        $request = Request::create('/json-rpc', 'GET');
        $expectedContent = '{"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with(
                $this->equalTo(''),
                $this->equalTo($request)
            )
            ->willReturn($expectedContent);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedContent, $response->getContent());
    }

    /**
     * 该测试需要mock全局函数JsonpCallbackValidator::validate
     * 由于我们不能使用Runkit扩展，这里只测试JSONP响应的基本形式
     */
    public function testHttpGet_withValidCallback_returnsJsonpResponse(): void
    {
        // 由于不能模拟JsonpCallbackValidator::validate，创建一个"假设有效"的情景
        $callback = 'validCallback';
        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $request = Request::create('/json-rpc', 'GET', [
            '__payload' => $payload,
            'callback' => $callback
        ]);
        $jsonContent = '{"jsonrpc": "2.0", "result": "success", "id": 1}';

        // 定义\JsonpCallbackValidator类，如果测试环境中没有这个类
        if (!class_exists('\JsonpCallbackValidator')) {
            // @codingStandardsIgnoreStart
            eval('class JsonpCallbackValidator { public static function validate($callback) { return $callback === "validCallback"; } }');
            // @codingStandardsIgnoreEnd
        }

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with(
                $this->equalTo($payload),
                $this->equalTo($request)
            )
            ->willReturn($jsonContent);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
        $this->assertEquals("{$callback}({$jsonContent})", $response->getContent());
    }

    public function testHttpGet_withInvalidCallback_returnsJsonResponse(): void
    {
        // 由于不能模拟JsonpCallbackValidator::validate，创建一个"假设无效"的情景
        $callback = 'invalid%Callback';
        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $request = Request::create('/json-rpc', 'GET', [
            '__payload' => $payload,
            'callback' => $callback
        ]);
        $jsonContent = '{"jsonrpc": "2.0", "result": "success", "id": 1}';

        // 定义\JsonpCallbackValidator类，如果测试环境中没有这个类
        if (!class_exists('\JsonpCallbackValidator')) {
            // @codingStandardsIgnoreStart
            eval('class JsonpCallbackValidator { public static function validate($callback) { return $callback === "validCallback"; } }');
            // @codingStandardsIgnoreEnd
        }

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with(
                $this->equalTo($payload),
                $this->equalTo($request)
            )
            ->willReturn($jsonContent);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($jsonContent, $response->getContent());
    }
} 