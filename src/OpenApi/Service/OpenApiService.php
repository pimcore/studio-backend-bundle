<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Service;

use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use function is_string;

final readonly class OpenApiService implements OpenApiServiceInterface
{
    private const TRANSLATABLE_CRUD_METHODS = ['get', 'put', 'post', 'delete', 'options'];

    private const TRANSLATABLE_PATH_PROPERTIES = ['summary', 'description'];

    private const TRANSLATABLE_TAG_PROPERTIES = ['description'];

    public function __construct(
        private TranslatorServiceInterface $translator,
        private array $openApiScanPaths = []
    ) {
    }

    public function getConfig(): OpenApi
    {
        $config = Generator::scan([...$this->openApiScanPaths]);

        if ($config) {
            usort($config->components->schemas, [$this, 'sortSchemas']);
        }

        $this->translateConfig($config);

        return $config;
    }

    private function sortSchemas(Schema $a, Schema $b): int
    {
        return $a->title <=> $b->title;
    }

    private function translateConfig(OpenApi $config): void
    {
        $this->translatePathProperties($config);
        $this->translateTagsProperties($config);
        $this->translateSchemaDescriptions($config);

    }

    private function translatePathProperties(OpenApi $config): void
    {
        foreach ($config->paths as $path) {
            foreach (self::TRANSLATABLE_CRUD_METHODS as $method) {
                if (is_string($path->{$method})) {
                    continue;
                }

                foreach (self::TRANSLATABLE_PATH_PROPERTIES as $property) {
                    if ((string)$path->{$method}->{$property} === Generator::UNDEFINED) {
                        continue;
                    }
                    $path->{$method}->{$property} = $this->translate((string)$path->{$method}->{$property});
                }
            }
        }
    }

    private function translateSchemaDescriptions(OpenApi $config): void
    {
        foreach ($config->components->schemas as $schema) {
            if ($schema->description === Generator::UNDEFINED) {
                continue;
            }
            $schema->description = $this->translate($schema->description);
        }
    }

    private function translateTagsProperties(OpenApi $config): void
    {
        foreach ($config->tags as $tag) {
            foreach (self::TRANSLATABLE_TAG_PROPERTIES as $property) {
                if ((string)$tag->{$property} === Generator::UNDEFINED) {
                    continue;
                }
                $tag->{$property} = $this->translate((string)$tag->{$property});
            }
        }
    }

    private function translate(string $message): string
    {
        return $this->translator->translateApiDocs($message);
    }
}
