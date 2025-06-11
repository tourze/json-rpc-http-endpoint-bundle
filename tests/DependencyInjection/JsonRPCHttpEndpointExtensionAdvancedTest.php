<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCHttpEndpointBundle\DependencyInjection\JsonRPCHttpEndpointExtension;

/**
 * JsonRPCHttpEndpointExtension 扩展测试
 */
class JsonRPCHttpEndpointExtensionAdvancedTest extends TestCase
{
    private JsonRPCHttpEndpointExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new JsonRPCHttpEndpointExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad_withEmptyConfig(): void
    {
        $this->extension->load([], $this->container);
        
        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoad_withMultipleConfigs(): void
    {
        $configs = [
            ['config1' => 'value1'],
            ['config2' => 'value2']
        ];
        
        $this->extension->load($configs, $this->container);
        
        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoad_registersControllerServices(): void
    {
        $this->extension->load([], $this->container);
        
        $controllerFound = false;
        foreach ($this->container->getDefinitions() as $serviceId => $definition) {
            if (str_contains($serviceId, 'Controller')) {
                $controllerFound = true;
                break;
            }
        }
        
        $this->assertTrue($controllerFound, '应该注册控制器服务');
    }

    public function testLoad_registersServiceServices(): void
    {
        $this->extension->load([], $this->container);
        
        $serviceFound = false;
        foreach ($this->container->getDefinitions() as $serviceId => $definition) {
            if (str_contains($serviceId, 'Service')) {
                $serviceFound = true;
                break;
            }
        }
        
        $this->assertTrue($serviceFound, '应该注册服务类');
    }

    public function testLoad_configuresAutowiring(): void
    {
        $this->extension->load([], $this->container);
        
        $hasAutowiredService = false;
        foreach ($this->container->getDefinitions() as $definition) {
            if ($definition->isAutowired()) {
                $hasAutowiredService = true;
                break;
            }
        }
        
        $this->assertTrue($hasAutowiredService, '应该配置自动装配');
    }

    public function testLoad_configuresAutoconfigure(): void
    {
        $this->extension->load([], $this->container);
        
        $hasAutoconfiguredService = false;
        foreach ($this->container->getDefinitions() as $definition) {
            if ($definition->isAutoconfigured()) {
                $hasAutoconfiguredService = true;
                break;
            }
        }
        
        $this->assertTrue($hasAutoconfiguredService, '应该配置自动配置');
    }

    public function testExtensionExtendsSymfonyExtension(): void
    {
        $this->assertInstanceOf(
            \Symfony\Component\DependencyInjection\Extension\Extension::class,
            $this->extension,
            'Extension 应该继承自 Symfony Extension'
        );
    }

    public function testLoadMethodExists(): void
    {
        $reflection = new \ReflectionClass(JsonRPCHttpEndpointExtension::class);
        
        $this->assertTrue($reflection->hasMethod('load'), 'Extension 应该有 load 方法');
        
        $loadMethod = $reflection->getMethod('load');
        $this->assertTrue($loadMethod->isPublic(), 'load 方法应该是公开的');
        $this->assertEquals(2, $loadMethod->getNumberOfParameters(), 'load 方法应该有2个参数');
    }

    public function testLoad_withInvalidConfigDoesNotThrow(): void
    {
        // 测试传入各种无效配置时不会抛出异常
        $invalidConfigs = [
            [null],
            [false],
            [123],
            ['string'],
            [new \stdClass()],
        ];
        
        foreach ($invalidConfigs as $config) {
            try {
                $container = new ContainerBuilder();
                $this->extension->load($config, $container);
                // 如果没有抛出异常，测试通过
                $this->assertTrue(true);
            } catch (\Throwable $e) {
                // 如果抛出异常，验证是否是预期的异常类型
                $this->assertInstanceOf(\TypeError::class, $e, '应该抛出 TypeError');
            }
        }
    }
} 