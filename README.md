# JSON-RPC HTTP Endpoint Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?branch=master)]
(https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)]
(https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle that provides HTTP endpoints for JSON-RPC services. This bundle creates HTTP controllers that expose JSON-RPC endpoints with support for POST, GET, and OPTIONS methods, as well as JSONP callbacks.

## Installation

```bash
composer require tourze/json-rpc-http-endpoint-bundle
```

## Quick Start

1. Add the bundle to your `config/bundles.php`:

```php
<?php
return [
    // ... other bundles
    Tourze\JsonRPCHttpEndpointBundle\JsonRPCHttpEndpointBundle::class => ['all' => true],
];
```

2. The bundle automatically registers HTTP endpoints for JSON-RPC:
    - `POST /json-rpc` - Main JSON-RPC endpoint
    - `GET /json-rpc` - GET method with `__payload` parameter
    - `OPTIONS /json-rpc` - CORS preflight support

## Features

- **Multiple HTTP Methods**: Supports POST, GET, and OPTIONS requests
- **JSONP Support**: GET requests support JSONP callbacks
- **CORS Support**: Built-in CORS headers for cross-origin requests
- **Legacy Routes**: Backward compatibility with existing API endpoints
- **Automatic Service Discovery**: Uses attribute-based controller loading

## Usage

### POST Requests

Send JSON-RPC requests via POST:

```bash
curl -X POST http://your-app.com/json-rpc \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "your.method", "params": {"param1": "value1"}, "id": 1}'
```

### GET Requests

Send JSON-RPC requests via GET with `__payload` parameter:

```bash
curl "http://your-app.com/json-rpc?__payload=%7B%22jsonrpc%22%3A%222.0%22%2C%22method%22%3A%22your.method%22%2C%22params%22%3A%7B%22param1%22%3A%22value1%22%7D%2C%22id%22%3A1%7D"
```

### JSONP Support

For JSONP requests, add a `callback` parameter:

```bash
curl "http://your-app.com/json-rpc?callback=myCallback&__payload=%7B...%7D"
```

## Configuration

The bundle works with the JSON-RPC endpoint system and requires the following dependencies:
- `tourze/json-rpc-endpoint-bundle` - Core JSON-RPC endpoint functionality
- `tourze/json-rpc-core` - JSON-RPC protocol implementation

## Available Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | `/json-rpc` | `json_rpc_http_server_endpoint` | Main JSON-RPC endpoint |
| POST | `/server/json-rpc` | `json_rpc_http_server_endpoint__legacy-1` | Legacy endpoint |
| GET | `/json-rpc` | `json_rpc_http_server_endpoint_get` | GET method endpoint |
| GET | `/api/json-rpc` | `json_rpc_http_api_endpoint_legacy` | Legacy GET endpoint |
| OPTIONS | `/json-rpc` | - | CORS preflight support |

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.