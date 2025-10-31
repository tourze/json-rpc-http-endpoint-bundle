<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * 测试JsonRPC HTTP端点的完整调用逻辑
 *
 * @internal
 */
#[CoversClass(JsonRpcController::class)]
#[RunTestsInSeparateProcesses]
final class JsonRpcEndpointTest extends AbstractWebTestCase
{
    public function testHttpOptions(): void
    {
        $client = self::createClient();

        $client->request('OPTIONS', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testHttpOptionsWithXml(): void
    {
        $client = self::createClient();

        $client->request('OPTIONS', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testHttpPost(): void
    {
        $client = self::createClient();

        $jsonRpcRequest = new JsonRpcRequest();
        $jsonRpcRequest->setMethod('ping');
        $jsonRpcRequest->setId(1);

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $jsonRpcRequest);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpPostWithEmptyBody(): void
    {
        $client = self::createClient();

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGet(): void
    {
        $client = self::createClient();

        $jsonRpcPayload = '{"jsonrpc":"2.0","method":"ping","id":1}';

        $client->request('GET', '/json-rpc', ['__payload' => $jsonRpcPayload]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithJsonpCallback(): void
    {
        $client = self::createClient();

        $jsonRpcPayload = '{"jsonrpc":"2.0","method":"ping","id":1}';
        $callback = 'myCallback';

        $client->request('GET', '/json-rpc', [
            '__payload' => $jsonRpcPayload,
            'callback' => $callback,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $contentType = $response->headers->get('Content-Type');
        $this->assertContains($contentType, ['application/json', 'application/javascript']);
        $this->assertNotEmpty($response->getContent());
    }

    public function testHttpGetWithMissingPayload(): void
    {
        $client = self::createClient();

        $client->request('GET', '/json-rpc');

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('jsonrpc', $responseData);
    }

    public function testHttpPostBatchRequest(): void
    {
        $client = self::createClient();

        $batchRequestData = [
            ['jsonrpc' => '2.0', 'method' => 'ping', 'id' => 1],
            ['jsonrpc' => '2.0', 'method' => 'ping', 'id' => 2],
        ];

        $encodedData = json_encode($batchRequestData);
        $this->assertIsString($encodedData);

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $encodedData);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
    }

    public function testInvalidJsonpCallback(): void
    {
        $client = self::createClient();

        $jsonRpcPayload = '{"jsonrpc":"2.0","method":"ping","id":1}';

        $client->request('GET', '/json-rpc', [
            '__payload' => $jsonRpcPayload,
            'callback' => 'invalid-callback!',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
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
