<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * JsonRpcController 高级测试场景
 *
 * @internal
 */
#[CoversClass(JsonRpcController::class)]
#[RunTestsInSeparateProcesses]
final class JsonRpcControllerAdvancedTest extends AbstractWebTestCase
{
    public function testHttpOptionsWithVariousContentTypes(): void
    {
        $client = self::createClient();
        $contentTypes = ['json', 'xml', 'yaml', 'custom'];

        foreach ($contentTypes as $type) {
            $client->request('OPTIONS', '/json-rpc', [], [], [
                'CONTENT_TYPE' => "application/{$type}",
                'HTTP_ACCEPT' => "application/{$type}",
            ]);

            $response = $client->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testHttpPostWithLargePayload(): void
    {
        $client = self::createClient();

        $largePayload = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'testMethod',
            'params' => array_fill(0, 100, 'large_data_string_' . str_repeat('x', 50)),
            'id' => 1,
        ]);
        $this->assertIsString($largePayload);

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $largePayload);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpPostWithNullContent(): void
    {
        $client = self::createClient();

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithUrlEncodedPayload(): void
    {
        $client = self::createClient();

        $originalPayload = '{"jsonrpc":"2.0","method":"test","params":{"key":"value with spaces"},"id":1}';
        $encodedPayload = urlencode($originalPayload);

        $client->request('GET', '/json-rpc', ['__payload' => $encodedPayload]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithEmptyCallback(): void
    {
        $client = self::createClient();

        $payload = '{"jsonrpc":"2.0","method":"test","id":1}';

        $client->request('GET', '/json-rpc', [
            '__payload' => $payload,
            'callback' => '',
        ]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithSpecialCharactersInPayload(): void
    {
        $client = self::createClient();

        $payload = '{"jsonrpc":"2.0","method":"test","params":{"message":"Hello, 世界! 🌍"},"id":1}';

        $client->request('GET', '/json-rpc', ['__payload' => $payload]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testConstants(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/json-rpc');
        $response = $client->getResponse();

        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('json_rpc_http_api_endpoint_legacy', JsonRpcController::LEGACY_ROUTE_NAME);
        $this->assertEquals('json_rpc_http_server_endpoint_get', JsonRpcController::GET_METHOD_ROUTE_NAME);
    }

    public function testHttpGetJsonpCallbackSecurityCheck(): void
    {
        $client = self::createClient();

        $testCases = [
            'validCallback',
            'valid_callback',
            'invalid-callback',
            'invalid.callback',
        ];

        foreach ($testCases as $callback) {
            $payload = '{"jsonrpc":"2.0","method":"test","id":1}';

            $client->request('GET', '/json-rpc', [
                '__payload' => $payload,
                'callback' => $callback,
            ]);

            $response = $client->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            // 显式将client注册到静态上下文以便断言方法使用
            self::getClient($client);
            $this->assertResponseIsSuccessful();

            $contentType = $response->headers->get('Content-Type');
            $this->assertContains($contentType, ['application/json', 'application/javascript']);
            $this->assertNotEmpty($response->getContent());
        }
    }

    public function testGetMethod(): void
    {
        $client = self::createClient();

        $client->request('GET', '/json-rpc');
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
    }

    public function testPostMethod(): void
    {
        $client = self::createClient();

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"jsonrpc":"2.0","method":"test","id":1}');

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClient();

        $client->request('OPTIONS', '/json-rpc');
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClient();

        $client->request('GET', '/json-rpc');
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClient();

        $client->request($method, '/json-rpc');
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
    }
}
