<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tourze\JsonRPCHttpEndpointBundle\Service\AttributeControllerLoader;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * AttributeControllerLoader 扩展测试
 */
class AttributeControllerLoaderAdvancedTest extends TestCase
{
    private AttributeControllerLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }

    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        $this->assertInstanceOf(
            RoutingAutoLoaderInterface::class,
            $this->loader,
            'AttributeControllerLoader 应该实现 RoutingAutoLoaderInterface'
        );
    }

    public function testExtendsSymfonyLoader(): void
    {
        $this->assertInstanceOf(
            Loader::class,
            $this->loader,
            'AttributeControllerLoader 应该继承自 Symfony Loader'
        );
    }

    public function testSupports_withVariousInputs(): void
    {
        $testCases = [
            [null, null, false],
            ['', '', false],
            ['resource', null, false],
            ['resource', 'type', false],
            [123, 'type', false],
            [[], 'type', false],
            [new \stdClass(), 'type', false],
        ];

        foreach ($testCases as $index => [$resource, $type, $expected]) {
            $this->assertEquals(
                $expected,
                $this->loader->supports($resource, $type),
                "Test case {$index}: supports() should return {$expected}"
            );
        }
    }

    public function testAutoload_returnsRouteCollectionWithRoutes(): void
    {
        $routeCollection = $this->loader->autoload();
        
        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
        
        // 验证路由集合不为空（JsonRpcController有路由定义）
        $this->assertGreaterThan(0, $routeCollection->count(), '路由集合应该包含路由');
    }

    public function testAutoload_containsExpectedRoutes(): void
    {
        $routeCollection = $this->loader->autoload();
        
        $routeNames = array_keys($routeCollection->all());
        
        // 检查是否包含期望的路由名称
        $expectedRoutes = [
            'json_rpc_http_server_endpoint',
            'json_rpc_http_server_endpoint__legacy-1',
            'json_rpc_http_api_endpoint_legacy',
            'json_rpc_http_server_endpoint_get'
        ];
        
        foreach ($expectedRoutes as $expectedRoute) {
            $this->assertContains(
                $expectedRoute,
                $routeNames,
                "路由集合应该包含路由: {$expectedRoute}"
            );
        }
    }

    public function testAutoload_routesHaveCorrectMethods(): void
    {
        $routeCollection = $this->loader->autoload();
        
        foreach ($routeCollection->all() as $route) {
            $this->assertInstanceOf(Route::class, $route);
            
            $methods = $route->getMethods();
            $this->assertNotEmpty($methods, '每个路由都应该定义HTTP方法');
            
            // 验证方法是有效的HTTP方法
            $validMethods = ['GET', 'POST', 'OPTIONS', 'PUT', 'DELETE', 'PATCH'];
            foreach ($methods as $method) {
                $this->assertContains($method, $validMethods, "HTTP方法 {$method} 应该是有效的");
            }
        }
    }

    public function testAutoload_routesHaveControllerActions(): void
    {
        $routeCollection = $this->loader->autoload();
        
        foreach ($routeCollection->all() as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            
            $this->assertNotNull($controller, "路由 {$routeName} 应该有控制器定义");
            $this->assertStringContainsString('JsonRpcController', $controller, "控制器应该指向 JsonRpcController");
        }
    }

    public function testLoad_delegatesToAutoload(): void
    {
        // 测试 load 方法实际调用了 autoload
        $result1 = $this->loader->load('any_resource', 'any_type');
        $result2 = $this->loader->autoload();
        
        // load 和 autoload 应该返回相同的结果
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertEquals(array_keys($result1->all()), array_keys($result2->all()));
    }

    public function testAutoload_isConsistent(): void
    {
        // 多次调用autoload应该返回相同的路由
        $collection1 = $this->loader->autoload();
        $collection2 = $this->loader->autoload();
        
        $this->assertEquals(
            $collection1->count(),
            $collection2->count(),
            'autoload 应该每次返回相同数量的路由'
        );
        
        $routes1 = array_keys($collection1->all());
        $routes2 = array_keys($collection2->all());
        
        sort($routes1);
        sort($routes2);
        
        $this->assertEquals($routes1, $routes2, 'autoload 应该每次返回相同的路由名称');
    }

    public function testAutoload_routePathsAreCorrect(): void
    {
        $routeCollection = $this->loader->autoload();
        
        $expectedPaths = [
            '/json-rpc',
            '/server/json-rpc',
            '/api/json-rpc'
        ];
        
        $actualPaths = [];
        foreach ($routeCollection->all() as $route) {
            $actualPaths[] = $route->getPath();
        }
        
        foreach ($expectedPaths as $expectedPath) {
            $this->assertContains(
                $expectedPath,
                $actualPaths,
                "应该包含路径: {$expectedPath}"
            );
        }
    }
} 