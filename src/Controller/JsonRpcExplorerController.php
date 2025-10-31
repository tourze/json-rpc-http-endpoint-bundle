<?php

namespace Tourze\JsonRPCHttpEndpointBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodReturn;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;

final class JsonRpcExplorerController extends AbstractController
{
    /**
     * @param ServiceProviderInterface<JsonRpcMethodInterface> $methodLocator
     */
    public function __construct(
        #[Autowire(service: 'json_rpc_http_server.service_locator.method_resolver')]
        private readonly ServiceProviderInterface $methodLocator,
    ) {
    }

    #[Route(path: '/json-rpc/explorer', name: 'json_rpc_explorer', methods: ['GET'])]
    public function __invoke(): Response
    {
        $methods = $this->getAllMethods();

        return $this->render('@JsonRPCHttpEndpoint/explorer.html.twig', [
            'methods' => $methods,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getAllMethods(): array
    {
        $methods = [];
        $methodNames = array_keys($this->methodLocator->getProvidedServices());

        foreach ($methodNames as $methodName) {
            if ($this->methodLocator->has($methodName)) {
                $method = $this->methodLocator->get($methodName);
                if ($method instanceof JsonRpcMethodInterface) {
                    $metadata = $this->extractMethodMetadata($method, $methodName);
                    $metadata['markdown'] = $this->generateMarkdownForMethod($metadata);
                    $methods[$methodName] = $metadata;
                }
            }
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMethodMetadata(JsonRpcMethodInterface $method, string $methodName): array
    {
        $reflection = new \ReflectionClass($method);

        $metadata = [
            'name' => $methodName,
            'class' => $reflection->getName(),
            'summary' => '',
            'description' => '',
            'tag' => '默认',
            'parameters' => [],
            'returns' => null,
            'requiresAuth' => false,
        ];

        // 提取 MethodExpose 注解
        $exposeAttributes = $reflection->getAttributes(MethodExpose::class);
        if (count($exposeAttributes) > 0) {
            $expose = $exposeAttributes[0]->newInstance();
            $metadata['name'] = $expose->method;
        }

        // 提取 MethodDoc 注解
        $docAttributes = $reflection->getAttributes(MethodDoc::class);
        if (count($docAttributes) > 0) {
            $doc = $docAttributes[0]->newInstance();
            $metadata['summary'] = $doc->summary;
            $metadata['description'] = $doc->description;
        }

        // 提取 MethodTag 注解
        $tagAttributes = $reflection->getAttributes(MethodTag::class);
        if (count($tagAttributes) > 0) {
            $tag = $tagAttributes[0]->newInstance();
            $metadata['tag'] = $tag->name;
        }

        // 提取 MethodReturn 注解
        $returnAttributes = $reflection->getAttributes(MethodReturn::class);
        if (count($returnAttributes) > 0) {
            $returnAttr = $returnAttributes[0]->newInstance();
            $metadata['returns'] = [
                'description' => $returnAttr->description,
                'type' => 'mixed',
            ];
        }

        // 检查是否需要登录验证
        $isGrantedAttributes = $reflection->getAttributes('Symfony\Component\Security\Http\Attribute\IsGranted');
        if (count($isGrantedAttributes) > 0) {
            $metadata['requiresAuth'] = true;
        }

        // 提取参数信息
        $metadata['parameters'] = $this->extractParameters($reflection);

        return $metadata;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return array<string, array<string, mixed>>
     */
    private function extractParameters(\ReflectionClass $reflection): array
    {
        $parameters = [];

        foreach ($reflection->getProperties() as $property) {
            $paramAttributes = $property->getAttributes(MethodParam::class);
            if (count($paramAttributes) > 0) {
                $param = $paramAttributes[0]->newInstance();
                $parameters[$property->getName()] = [
                    'name' => $property->getName(),
                    'description' => $param->description,
                    'optional' => $param->optional,
                    'type' => $this->getPropertyTypeName($property),
                ];
            }
        }

        return $parameters;
    }

    private function getPropertyTypeName(\ReflectionProperty $property): string
    {
        $type = $property->getType();
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        if ($type instanceof \ReflectionUnionType) {
            $types = array_map(fn (\ReflectionType $t) => $t instanceof \ReflectionNamedType ? $t->getName() : 'mixed', $type->getTypes());

            return implode('|', $types);
        }

        return 'mixed';
    }

    /**
     * 生成接口的 Markdown 格式文档
     * @param array<string, mixed> $metadata
     */
    private function generateMarkdownForMethod(array $metadata): string
    {
        $markdown = "# JsonRPC 接口: {$metadata['name']}\n\n";

        $markdown .= $this->generateSummarySection($metadata);
        $markdown .= $this->generateBasicInfoSection($metadata);
        $markdown .= $this->generateParametersSection($metadata);
        $markdown .= $this->generateReturnsSection($metadata);
        $markdown .= $this->generateCallExampleSection($metadata);

        $markdown .= "\n";

        return $markdown;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function generateSummarySection(array $metadata): string
    {
        $section = '';
        if (isset($metadata['summary']) && '' !== $metadata['summary']) {
            $section .= "## 摘要\n{$metadata['summary']}\n\n";
        }
        if (isset($metadata['description']) && '' !== $metadata['description']) {
            $section .= "## 描述\n{$metadata['description']}\n\n";
        }

        return $section;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function generateBasicInfoSection(array $metadata): string
    {
        $section = "## 基本信息\n";
        $section .= "- **方法名**: `{$metadata['name']}`\n";
        $section .= "- **分类**: {$metadata['tag']}\n";
        $section .= "- **实现类**: `{$metadata['class']}`\n";
        $section .= '- **需要认证**: ' . (true === $metadata['requiresAuth'] ? '是 🔒' : '否') . "\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function generateParametersSection(array $metadata): string
    {
        if (!isset($metadata['parameters']) || 0 === count($metadata['parameters'])) {
            return '';
        }

        $section = "## 参数\n\n";
        foreach ($metadata['parameters'] as $param) {
            $optional = true === $param['optional'] ? '（可选）' : '（必需）';
            $section .= "### `{$param['name']}` {$optional}\n";
            $section .= "- **类型**: `{$param['type']}`\n";
            if (isset($param['description']) && '' !== $param['description']) {
                $section .= "- **描述**: {$param['description']}\n";
            }
            $section .= "\n";
        }

        return $section;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function generateReturnsSection(array $metadata): string
    {
        if (!isset($metadata['returns'])) {
            return '';
        }

        $section = "## 返回值\n";
        $section .= "- **类型**: `{$metadata['returns']['type']}`\n";
        if (isset($metadata['returns']['description']) && '' !== $metadata['returns']['description']) {
            $section .= "- **描述**: {$metadata['returns']['description']}\n";
        }
        $section .= "\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function generateCallExampleSection(array $metadata): string
    {
        $section = "## 调用示例\n";
        $section .= "```json\n";
        $section .= "{\n";
        $section .= "  \"jsonrpc\": \"2.0\",\n";
        $section .= "  \"method\": \"{$metadata['name']}\",\n";
        $section .= "  \"params\": {\n";

        if (isset($metadata['parameters']) && count($metadata['parameters']) > 0) {
            $paramExamples = [];
            foreach ($metadata['parameters'] as $param) {
                $exampleValue = $this->getExampleValue($param['type']);
                $paramExamples[] = "    \"{$param['name']}\": {$exampleValue}";
            }
            $section .= implode(",\n", $paramExamples) . "\n";
        }

        $section .= "  },\n";
        $section .= "  \"id\": 1\n";
        $section .= "}\n";
        $section .= "```\n\n";

        return $section;
    }

    private function getExampleValue(string $type): string
    {
        return match (strtolower($type)) {
            'string' => '"示例文本"',
            'int', 'integer' => '123',
            'float', 'double' => '12.34',
            'bool', 'boolean' => 'true',
            'array' => '[]',
            'object' => '{}',
            default => '"示例值"',
        };
    }
}
