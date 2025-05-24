<?php

namespace Tourze\JsonRPCHttpEndpointBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;

class JsonRPCHttpEndpointBundle extends Bundle implements BundleDependencyInterface
{
    public function boot(): void
    {
        parent::boot();
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(JsonRpcController::class))->getFileName());
    }

    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle::class => ['all' => true],
        ];
    }
}
