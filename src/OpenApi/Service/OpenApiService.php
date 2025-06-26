<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Service;

use JsonException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Tag;
use OpenApi\Attributes\Schema;
use OpenApi\Attributes\Server;
use OpenApi\Generator;
use Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

final readonly class OpenApiService implements OpenApiServiceInterface
{
    private const array TRANSLATABLE_PROPERTIES = ['summary', 'description'];

    public function __construct(
        private PimcoreBundleManager $pimcoreBundleManager,
        private TranslatorServiceInterface $translator,
        private string $routePrefix,
        private array $openApiScanPaths = [],
        private array $openApiServers = []
    ) {
    }

    /**
     * @throws InvalidPathException
     */
    public function getConfig(): OpenApi
    {
        $this->checkValidOpenApiScanPaths();

        $config = Generator::scan([...$this->openApiScanPaths]);

        if ($config) {
            $this->filterConfigs($config);

            usort($config->components->schemas, [$this, 'sortSchemas']);

            // replace the configurable prefix in the paths
            $prefix = $this->routePrefix;
            $config->paths = array_map(
                static function (PathItem $pathItem) use ($prefix) {
                    $pathItem->path = str_replace('{prefix}', $prefix, $pathItem->path);

                    return $pathItem;
                },
                $config->paths
            );

            $this->addServers($config);
        }

        return $config;
    }

    public function translateConfig(OpenApi $config, string $locale = 'en'): array
    {
        try {

            $configArray = json_decode(
                json_encode(
                    $config,
                    JSON_THROW_ON_ERROR
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new EnvironmentException('Failed to convert OpenAPI config to array');
        }

        $this->translateRecursive($configArray, $locale);

        return $configArray;
    }

    private function sortSchemas(Schema $a, Schema $b): int
    {
        return $a->title <=> $b->title;
    }

    private function translateRecursive(array &$config, string $locale = 'en'): void
    {
        foreach ($config as $key => &$value) {
            if (!is_array($value) && is_string($key) && in_array($key, self::TRANSLATABLE_PROPERTIES)) {
                $value = $this->translate($value, $locale);

                continue;
            }

            if (is_array($value)) {
                $this->translateRecursive($value, $locale); //Recurse into sub-array
            }
        }
    }

    private function translate(string $message, string $locale = 'en'): string
    {
        return $this->translator->translateApiDocs($message, $locale);
    }

    private function addServers(OpenApi $config): void
    {
        if (empty($this->openApiServers)) {
            return;
        }

        $config->servers = array_map(
            static fn (array $server) => new Server(url: $server['url'], description: $server['description']),
            $this->openApiServers
        );
    }

    /**
     * @throws InvalidPathException
     */
    private function checkValidOpenApiScanPaths(): void
    {
        foreach ($this->openApiScanPaths as $path) {
            if (!is_dir($path)) {
                throw new InvalidPathException(
                    sprintf(
                        'The path "%s" is not a valid directory.',
                        $path
                    )
                );
            }
        }
    }

    private function filterConfigs(OpenApi $config): void
    {
        $this->filterConfigByBundle(
            $config,
            PimcoreApplicationLoggerBundle::class,
            '{prefix}/bundle/application-logger',
            'Bundle Application Logger'
        );

        $this->filterConfigByBundle(
            $config,
            PimcoreSeoBundle::class,
            '{prefix}/bundle/seo',
            'Bundle Seo'
        );
    }

    private function filterConfigByBundle(
        OpenApi $config,
        string $bundle,
        string $routePrefix,
        string $tagName
    ): void {
        try {
            $this->pimcoreBundleManager->getActiveBundle($bundle);

            return;
        } catch (BundleNotFoundException) {
            // If the bundle is not present, we will filter out paths and schemas
        }

        foreach ($config->paths as $path => $pathItem) {
            if (str_contains($pathItem->path, $routePrefix)) {
                unset($config->paths[$path]);
            }
        }

        foreach ($config->components->schemas as $index => $schema) {
            if (str_starts_with($schema->title, $tagName)) {
                unset($config->components->schemas[$index]);
            }
        }

        $config->components->schemas = array_values($config->components->schemas);

        $config->tags = array_filter(
            $config->tags,
            static fn (Tag $tag) => $tag->name !== $tagName
        );
        $config->tags = array_values($config->tags);
    }
}
