<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\JsonRPCHttpEndpointBundle\DependencyInjection\JsonRPCHttpEndpointExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * JsonRPCHttpEndpointExtension 扩展测试
 *
 * @internal
 */
#[CoversClass(JsonRPCHttpEndpointExtension::class)]
final class JsonRPCHttpEndpointExtensionAdvancedTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionExtendsAutoExtension(): void
    {
        $reflection = new \ReflectionClass(JsonRPCHttpEndpointExtension::class);
        $this->assertTrue($reflection->isSubclassOf(AutoExtension::class), 'Extension 应该继承自 AutoExtension');
    }

    public function testGetConfigDirMethodExists(): void
    {
        $reflection = new \ReflectionClass(JsonRPCHttpEndpointExtension::class);

        $this->assertTrue($reflection->hasMethod('getConfigDir'), 'Extension 应该有 getConfigDir 方法');

        $getConfigDirMethod = $reflection->getMethod('getConfigDir');
        $this->assertTrue($getConfigDirMethod->isProtected(), 'getConfigDir 方法应该是保护的');
        $this->assertEquals(0, $getConfigDirMethod->getNumberOfParameters(), 'getConfigDir 方法应该没有参数');
    }
}
