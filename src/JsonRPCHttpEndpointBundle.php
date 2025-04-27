<?php

namespace Tourze\JsonRPCHttpEndpointBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\JsonRPCHttpEndpointBundle\Controller\JsonRpcController;

class JsonRPCHttpEndpointBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(JsonRpcController::class))->getFileName());
    }
}
