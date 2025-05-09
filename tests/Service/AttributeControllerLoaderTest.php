<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;
use Tourze\JsonRPCHttpEndpointBundle\Service\AttributeControllerLoader;

class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }

    public function testSupports_returnsFalse(): void
    {
        $this->assertFalse($this->loader->supports('resource'));
        $this->assertFalse($this->loader->supports('resource', 'type'));
        $this->assertFalse($this->loader->supports(null));
    }

    public function testLoad_callsAutoload(): void
    {
        $loaderMock = $this->createPartialMock(AttributeControllerLoader::class, ['autoload']);
        
        $routeCollection = new RouteCollection();
        $loaderMock->expects($this->once())
            ->method('autoload')
            ->willReturn($routeCollection);
        
        $result = $loaderMock->load('resource', 'type');
        
        $this->assertSame($routeCollection, $result);
    }

    public function testAutoload_returnsRouteCollection(): void
    {
        // 由于AttributeRouteControllerLoader是框架类，难以直接模拟
        // 这里我们测试autoload方法至少返回一个RouteCollection对象
        
        $result = $this->loader->autoload();
        
        $this->assertInstanceOf(RouteCollection::class, $result);
    }
} 