# JSON-RPC HTTP Endpoint Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?branch=master)]
(https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)]
(https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

一个为 JSON-RPC 服务提供 HTTP 端点的 Symfony Bundle。此 Bundle 创建 HTTP 控制器，公开 JSON-RPC 端点，支持 POST、GET 和 OPTIONS 方法，以及 JSONP 回调。

## 安装

```bash
composer require tourze/json-rpc-http-endpoint-bundle
```

## 快速开始

1. 将 Bundle 添加到您的 `config/bundles.php`：

```php
<?php
return [
    // ... 其他 bundles
    Tourze\JsonRPCHttpEndpointBundle\JsonRPCHttpEndpointBundle::class => ['all' => true],
];
```

2. Bundle 会自动注册 JSON-RPC 的 HTTP 端点：
    - `POST /json-rpc` - 主要的 JSON-RPC 端点
    - `GET /json-rpc` - 使用 `__payload` 参数的 GET 方法
    - `OPTIONS /json-rpc` - CORS 预检支持

## 功能特性

- **多种 HTTP 方法**：支持 POST、GET 和 OPTIONS 请求
- **JSONP 支持**：GET 请求支持 JSONP 回调
- **CORS 支持**：内置 CORS 头部，支持跨域请求
- **兼容性路由**：向后兼容现有的 API 端点
- **自动服务发现**：使用基于属性的控制器加载

## 使用方法

### POST 请求

通过 POST 发送 JSON-RPC 请求：

```bash
curl -X POST http://your-app.com/json-rpc \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "your.method", "params": {"param1": "value1"}, "id": 1}'
```

### GET 请求

通过 GET 方法使用 `__payload` 参数发送 JSON-RPC 请求：

```bash
curl "http://your-app.com/json-rpc?__payload=%7B%22jsonrpc%22%3A%222.0%22%2C%22method%22%3A%22your.method%22%2C%22params%22%3A%7B%22param1%22%3A%22value1%22%7D%2C%22id%22%3A1%7D"
```

### JSONP 支持

对于 JSONP 请求，添加 `callback` 参数：

```bash
curl "http://your-app.com/json-rpc?callback=myCallback&__payload=%7B...%7D"
```

## 配置

该 Bundle 与 JSON-RPC 端点系统配合使用，需要以下依赖：
- `tourze/json-rpc-endpoint-bundle` - 核心 JSON-RPC 端点功能
- `tourze/json-rpc-core` - JSON-RPC 协议实现

## 可用路由

| 方法 | 路径 | 名称 | 描述 |
|------|------|------|------|
| POST | `/json-rpc` | `json_rpc_http_server_endpoint` | 主要的 JSON-RPC 端点 |
| POST | `/server/json-rpc` | `json_rpc_http_server_endpoint__legacy-1` | 遗留端点 |
| GET | `/json-rpc` | `json_rpc_http_server_endpoint_get` | GET 方法端点 |
| GET | `/api/json-rpc` | `json_rpc_http_api_endpoint_legacy` | 遗留 GET 端点 |
| OPTIONS | `/json-rpc` | - | CORS 预检支持 |

## 许可证

该包遵循 MIT 许可证。详情请参阅 [LICENSE](LICENSE) 文件。
