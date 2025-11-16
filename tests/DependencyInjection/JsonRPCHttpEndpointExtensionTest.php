<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCHttpEndpointBundle\DependencyInjection\JsonRPCHttpEndpointExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRPCHttpEndpointExtension::class)]
final class JsonRPCHttpEndpointExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoadLoadsServicesYaml(): void
    {
        // 创建一个真实的ContainerBuilder而不是模拟
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 创建扩展对象
        $extension = new JsonRPCHttpEndpointExtension();

        try {
            // 执行load方法
            $extension->load([], $container);

            // 验证至少有一个服务被注册
            $this->assertGreaterThan(
                0,
                $container->getDefinitions(),
                'JsonRPCHttpEndpointExtension应该注册至少一个服务'
            );

            // 检查是否注册了Controller和Service
            $controllerDefinitionExists = false;
            $serviceDefinitionExists = false;

            foreach ($container->getDefinitions() as $id => $definition) {
                if (str_starts_with($id, 'Tourze\JsonRPCHttpEndpointBundle\Controller\\')) {
                    $controllerDefinitionExists = true;
                }
                if (str_starts_with($id, 'Tourze\JsonRPCHttpEndpointBundle\Service\\')) {
                    $serviceDefinitionExists = true;
                }
            }

            $this->assertTrue($controllerDefinitionExists || $serviceDefinitionExists,
                'JsonRPCHttpEndpointExtension应该注册控制器或服务定义');
        } catch (\Throwable $e) {
            throw new Exception('Exception thrown while testing extension load: ' . $e->getMessage());
        }
    }
}
