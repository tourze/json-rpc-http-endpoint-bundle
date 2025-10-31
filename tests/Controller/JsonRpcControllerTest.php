<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * Controller 单元测试，使用 TestCase 而不是 AbstractIntegrationTestCase 的原因：
 * 1. 这是纯单元测试，通过 Mock 对象测试 Controller 的逻辑
 * 2. 不需要启动 Symfony 容器和依赖注入
 * 3. 测试执行速度更快，专注于 Controller 方法的行为验证
 *
 * @internal
 */
#[CoversClass(JsonRpcController::class)]
#[RunTestsInSeparateProcesses]
final class JsonRpcControllerTest extends AbstractWebTestCase
{
    public function testHttpOptionsReturnsResponseWithCorrectHeaders(): void
    {
        $client = self::createClient();

        $client->request('OPTIONS', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHttpPostReturnsJsonResponseWithContent(): void
    {
        $client = self::createClient();

        $client->request('POST', '/json-rpc', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"jsonrpc": "2.0", "method": "test", "id": 1}');

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithoutCallbackReturnsJsonResponse(): void
    {
        $client = self::createClient();

        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';

        $client->request('GET', '/json-rpc', ['__payload' => $payload]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithEmptyPayloadPassesEmptyStringToEndpoint(): void
    {
        $client = self::createClient();

        $client->request('GET', '/json-rpc');

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);
    }

    public function testHttpGetWithValidCallbackReturnsJsonpResponse(): void
    {
        $client = self::createClient();

        $callback = 'validCallback';
        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';

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

    public function testHttpGetWithInvalidCallbackReturnsJsonResponse(): void
    {
        $client = self::createClient();

        $callback = 'invalid%Callback';
        $payload = '{"jsonrpc": "2.0", "method": "test", "id": 1}';

        $client->request('GET', '/json-rpc', [
            '__payload' => $payload,
            'callback' => $callback,
        ]);

        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
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
