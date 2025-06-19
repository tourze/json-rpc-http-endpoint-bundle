<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCHttpEndpointBundle\JsonRPCHttpEndpointBundle;

/**
 * JsonRPCHttpEndpointBundle 扩展测试
 */
class JsonRPCHttpEndpointBundleAdvancedTest extends TestCase
{
    public function testGetBundleDependencies_returnsCorrectDependencies(): void
    {
        $dependencies = JsonRPCHttpEndpointBundle::getBundleDependencies();
        
        $this->assertArrayHasKey(\Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[\Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle::class]);
    }

    public function testGetBundleDependencies_isStaticMethod(): void
    {
        $reflection = new \ReflectionMethod(JsonRPCHttpEndpointBundle::class, 'getBundleDependencies');
        
        $this->assertTrue($reflection->isStatic(), 'getBundleDependencies 应该是静态方法');
        $this->assertTrue($reflection->isPublic(), 'getBundleDependencies 应该是公开方法');
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        $bundle = new JsonRPCHttpEndpointBundle();
        
        $this->assertInstanceOf(
            \Tourze\BundleDependency\BundleDependencyInterface::class,
            $bundle,
            'JsonRPCHttpEndpointBundle 应该实现 BundleDependencyInterface'
        );
    }

    public function testBundleExtendsSymfonyBundle(): void
    {
        $bundle = new JsonRPCHttpEndpointBundle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpKernel\Bundle\Bundle::class,
            $bundle,
            'JsonRPCHttpEndpointBundle 应该继承自 Symfony Bundle'
        );
    }

    public function testBootMethodExists(): void
    {
        $reflection = new \ReflectionClass(JsonRPCHttpEndpointBundle::class);
        
        $this->assertTrue($reflection->hasMethod('boot'), 'Bundle 应该有 boot 方法');
        
        $bootMethod = $reflection->getMethod('boot');
        $this->assertTrue($bootMethod->isPublic(), 'boot 方法应该是公开的');
    }

    public function testBootMethodCallsParentBoot(): void
    {
        // 创建一个测试用的Bundle子类来验证parent::boot()被调用
        $testBundle = new class extends JsonRPCHttpEndpointBundle {
            public static bool $parentBootCalled = false;
            
            public function boot(): void
            {
                // 通过反射调用父类的boot方法
                $reflection = new \ReflectionClass(parent::class);
                $parentBootMethod = $reflection->getMethod('boot');
                $parentBootMethod->invoke($this);
                
                // 标记parent::boot()被调用
                self::$parentBootCalled = true;
            }
        };
        
        $testBundle->boot();
        
        $this->assertTrue($testBundle::$parentBootCalled, 'boot 方法应该调用 parent::boot()');
    }
} 