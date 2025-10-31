<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcExplorerController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRpcExplorerController::class)]
#[RunTestsInSeparateProcesses]
class JsonRpcExplorerControllerTest extends AbstractWebTestCase
{
    public function testExplorerPageLoads(): void
    {
        $client = static::createClientWithDatabase();
        $client->request('GET', '/json-rpc/explorer');

        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testExplorerPageContainsExpectedElements(): void
    {
        $client = static::createClientWithDatabase();
        $crawler = $client->request('GET', '/json-rpc/explorer');

        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();

        // Check for page title
        $this->assertSelectorTextContains('title', 'JsonRPC API 探索器');

        // Check for header
        $this->assertSelectorTextContains('h1', 'JsonRPC API 探索器');

        // Check for token input
        $this->assertSelectorExists('#bearer-token');

        // Check for main container structure
        $this->assertSelectorExists('.main-container');
        $this->assertSelectorExists('.sidebar');
        $this->assertSelectorExists('.content');
    }

    public function testExplorerRouteConfiguration(): void
    {
        $client = static::createClientWithDatabase();

        // Test that the route is configured correctly
        $client->request('GET', '/json-rpc/explorer');
        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();

        // Test that POST method is not allowed
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/json-rpc/explorer');
    }

    public function testExplorerResponseContentType(): void
    {
        $client = static::createClientWithDatabase();
        $client->request('GET', '/json-rpc/explorer');

        // 显式将client注册到静态上下文以便断言方法使用
        self::getClient($client);
        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('text/html', $contentType);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = static::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/json-rpc/explorer');
    }
}
