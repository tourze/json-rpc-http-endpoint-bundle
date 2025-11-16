<?php

namespace Tourze\JsonRPCHttpEndpointBundle;

use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

class JsonRPCHttpEndpointBundle extends Bundle implements BundleDependencyInterface
{
    public function boot(): void
    {
        parent::boot();
        $filename = (new \ReflectionClass(JsonRpcController::class))->getFileName();
        if (false !== $filename) {
            Backtrace::addProdIgnoreFiles($filename);
        }
    }

    public static function getBundleDependencies(): array
    {
        return [
            JsonRPCEndpointBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
            TwigBundle::class => ['all' => true],
        ];
    }
}
