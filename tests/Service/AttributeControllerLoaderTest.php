<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\JsonRPCHttpEndpointBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    private function getLoader(): AttributeControllerLoader
    {
        return self::getService(AttributeControllerLoader::class);
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = $this->getLoader();
        $this->assertFalse($loader->supports('resource'));
        $this->assertFalse($loader->supports('resource', 'type'));
        $this->assertFalse($loader->supports(null));
    }

    public function testLoadDelegatesToAutoload(): void
    {
        $loader = $this->getLoader();
        $result = $loader->load('resource', 'type');
        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        // 由于AttributeRouteControllerLoader是框架类，难以直接模拟
        // 这里我们测试autoload方法至少返回一个RouteCollection对象

        $loader = $this->getLoader();
        $result = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }
}
