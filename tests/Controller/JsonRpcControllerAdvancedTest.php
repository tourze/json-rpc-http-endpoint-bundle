<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPCEndpointBundle\Service\JsonRpcEndpoint as SDKJsonRpcEndpoint;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;

/**
 * JsonRpcController 高级测试场景
 */
class JsonRpcControllerAdvancedTest extends TestCase
{
    private SDKJsonRpcEndpoint&MockObject $sdkEndpoint;
    private JsonRpcController $controller;

    protected function setUp(): void
    {
        $this->sdkEndpoint = $this->createMock(SDKJsonRpcEndpoint::class);
        $this->controller = new JsonRpcController($this->sdkEndpoint);
    }

    public function testHttpOptions_withVariousContentTypes(): void
    {
        $contentTypes = ['json', 'xml', 'yaml', 'custom'];
        
        foreach ($contentTypes as $type) {
            $response = $this->controller->httpOptions($type);
            
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals("application/{$type}", $response->headers->get('Content-Type'));
            $this->assertEquals("application/{$type}", $response->headers->get('Accept'));
        }
    }

    public function testHttpPost_withLargePayload(): void
    {
        // 测试大请求体处理 - 生成一个较大的 JSON payload
        $largePayload = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'testMethod',
            'params' => array_fill(0, 1000, 'large_data_string_' . str_repeat('x', 100)),
            'id' => 1
        ]);
        
        $request = Request::create('/json-rpc', 'POST', [], [], [], [], $largePayload);
        $expectedResponse = '{"jsonrpc":"2.0","result":"success","id":1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with($this->equalTo($largePayload), $this->equalTo($request))
            ->willReturn($expectedResponse);

        $response = $this->controller->httpPost($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedResponse, $response->getContent());
    }

    public function testHttpPost_withNullContent(): void
    {
        $request = Request::create('/json-rpc', 'POST', [], [], [], [], null);
        $expectedResponse = '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error"},"id":null}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with($this->equalTo(''), $this->equalTo($request))
            ->willReturn($expectedResponse);

        $response = $this->controller->httpPost($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedResponse, $response->getContent());
    }

    public function testHttpGet_withUrlEncodedPayload(): void
    {
        $originalPayload = '{"jsonrpc":"2.0","method":"test","params":{"key":"value with spaces"},"id":1}';
        $encodedPayload = urlencode($originalPayload);
        
        $request = Request::create('/json-rpc', 'GET', ['__payload' => $encodedPayload]);
        $expectedResponse = '{"jsonrpc":"2.0","result":"success","id":1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with($this->equalTo($encodedPayload), $this->equalTo($request))
            ->willReturn($expectedResponse);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedResponse, $response->getContent());
    }

    public function testHttpGet_withEmptyCallback(): void
    {
        $payload = '{"jsonrpc":"2.0","method":"test","id":1}';
        $request = Request::create('/json-rpc', 'GET', [
            '__payload' => $payload,
            'callback' => ''
        ]);
        $expectedResponse = '{"jsonrpc":"2.0","result":"success","id":1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with($this->equalTo($payload), $this->equalTo($request))
            ->willReturn($expectedResponse);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedResponse, $response->getContent());
    }

    public function testHttpGet_withSpecialCharactersInPayload(): void
    {
        $payload = '{"jsonrpc":"2.0","method":"test","params":{"message":"Hello, 世界! 🌍"},"id":1}';
        $request = Request::create('/json-rpc', 'GET', ['__payload' => $payload]);
        $expectedResponse = '{"jsonrpc":"2.0","result":"success","id":1}';

        $this->sdkEndpoint->expects($this->once())
            ->method('index')
            ->with($this->equalTo($payload), $this->equalTo($request))
            ->willReturn($expectedResponse);

        $response = $this->controller->httpGet($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($expectedResponse, $response->getContent());
    }

    public function testConstants(): void
    {
        $this->assertEquals('json_rpc_http_api_endpoint_legacy', JsonRpcController::LEGACY_ROUTE_NAME);
        $this->assertEquals('json_rpc_http_server_endpoint_get', JsonRpcController::GET_METHOD_ROUTE_NAME);
    }

    public function testHttpGet_jsonpCallbackSecurityCheck(): void
    {
        // 定义JsonpCallbackValidator类用于测试
        if (!class_exists('\JsonpCallbackValidator')) {
            eval('class JsonpCallbackValidator { 
                public static function validate($callback) { 
                    return preg_match("/^[a-zA-Z_$][a-zA-Z0-9_$]*$/", $callback) === 1; 
                } 
            }');
        }

        $testCases = [
            ['callback' => 'validCallback', 'valid' => true],
            ['callback' => 'valid_callback', 'valid' => true],
            ['callback' => '$validCallback', 'valid' => true],
            ['callback' => 'invalid-callback', 'valid' => false],
            ['callback' => 'invalid.callback', 'valid' => false],
            ['callback' => 'alert(1)', 'valid' => false],
            ['callback' => '<script>alert(1)</script>', 'valid' => false],
        ];

        foreach ($testCases as $testCase) {
            $payload = '{"jsonrpc":"2.0","method":"test","id":1}';
            $request = Request::create('/json-rpc', 'GET', [
                '__payload' => $payload,
                'callback' => $testCase['callback']
            ]);
            $jsonResponse = '{"jsonrpc":"2.0","result":"success","id":1}';

            $this->sdkEndpoint->expects($this->once())
                ->method('index')
                ->with($this->equalTo($payload), $this->equalTo($request))
                ->willReturn($jsonResponse);

            $response = $this->controller->httpGet($request);

            // 检查实际的 JsonpCallbackValidator 行为
            $isValidCallback = !empty($testCase['callback']) && \JsonpCallbackValidator::validate($testCase['callback']);
            
            if ($isValidCallback) {
                $this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
                $this->assertEquals($testCase['callback'] . '(' . $jsonResponse . ')', $response->getContent());
            } else {
                $this->assertEquals('application/json', $response->headers->get('Content-Type'));
                $this->assertEquals($jsonResponse, $response->getContent());
            }

            // 重置 mock 以便下次循环使用
            $this->sdkEndpoint = $this->createMock(SDKJsonRpcEndpoint::class);
            $this->controller = new JsonRpcController($this->sdkEndpoint);
        }
    }
} 