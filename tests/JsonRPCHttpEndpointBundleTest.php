<?php

declare(strict_types=1);

namespace Tourze\JsonRPCHttpEndpointBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPCHttpEndpointBundle\JsonRPCHttpEndpointBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRPCHttpEndpointBundle::class)]
#[RunTestsInSeparateProcesses]
final class JsonRPCHttpEndpointBundleTest extends AbstractBundleTestCase
{
    public function testBundleRegistersExtension(): void
    {
        $container = self::getContainer();
        $this->assertTrue($container->has('Tourze\JsonRPCHttpEndpointBundle\Service\AttributeControllerLoader'));
    }
}
