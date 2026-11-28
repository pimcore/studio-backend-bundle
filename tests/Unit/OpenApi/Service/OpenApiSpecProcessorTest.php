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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OpenApi\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiSpecProcessor;

final class OpenApiSpecProcessorTest extends Unit
{
    public function testCompareTagNamesSortsCoreTagsAlphabeticallyBeforeBundleTags(): void
    {
        $tags = ['Bundle Seo', 'Workflows', 'Assets', 'Bundle Custom Reports', 'Mercure'];
        usort($tags, [OpenApiSpecProcessor::class, 'compareTagNames']);

        $this->assertSame(
            ['Assets', 'Mercure', 'Workflows', 'Bundle Custom Reports', 'Bundle Seo'],
            $tags
        );
    }

    public function testRestrictToScopesIsSegmentAware(): void
    {
        $spec = $this->buildSpec();

        $result = OpenApiSpecProcessor::restrictToScopes($spec, ['/pimcore-studio/api/bundle/data-hub']);

        $this->assertSame(
            ['/pimcore-studio/api/bundle/data-hub/config', '/pimcore-studio/api/bundle/data-hub'],
            array_keys($result['paths'])
        );
    }

    public function testRestrictToScopesKeepsOnlyTransitivelyReferencedComponents(): void
    {
        $spec = $this->buildSpec();

        $result = OpenApiSpecProcessor::restrictToScopes($spec, ['/pimcore-studio/api/bundle/data-hub']);

        // source order is kept — only unreferenced components are dropped
        $this->assertSame(['DataHubConfig', 'Error', 'DevError'], array_keys($result['components']['schemas']));
        $this->assertArrayHasKey('securitySchemes', $result['components']);
    }

    public function testRestrictToScopesKeepsOnlyUsedTags(): void
    {
        $spec = $this->buildSpec();

        $result = OpenApiSpecProcessor::restrictToScopes($spec, ['/pimcore-studio/api/bundle/data-hub']);

        $this->assertSame(['Bundle Data Hub'], array_column($result['tags'], 'name'));
    }

    public function testRestrictToScopesWithoutScopesReturnsTheSpecUnchanged(): void
    {
        $spec = $this->buildSpec();

        $this->assertSame($spec, OpenApiSpecProcessor::restrictToScopes($spec, []));
    }

    public function testSortStableSortsAllKeysButKeepsPathsPropertiesAndResponsesOrder(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['version' => '1', 'title' => 'T'],
            'paths' => [
                '/b' => ['put' => ['responses' => ['default' => ['description' => 'x'], '200' => ['description' => 'y']]]],
                '/a' => ['get' => ['tags' => ['Z'], 'operationId' => 'a_get']],
            ],
            'components' => [
                'schemas' => [
                    'B' => ['type' => 'object', 'properties' => ['zebra' => ['type' => 'string'], 'alpha' => ['type' => 'string']]],
                    'A' => ['type' => 'object'],
                ],
            ],
        ];

        $result = OpenApiSpecProcessor::sortStable($spec, true);

        $this->assertSame(['components', 'info', 'openapi', 'paths'], array_keys($result));
        $this->assertSame(['/b', '/a'], array_keys($result['paths']));
        // PHP coerces the '200' key to int; the point is that the order is kept
        $this->assertSame(['default', 200], array_keys($result['paths']['/b']['put']['responses']));
        $this->assertSame(['A', 'B'], array_keys($result['components']['schemas']));
        $this->assertSame(['zebra', 'alpha'], array_keys($result['components']['schemas']['B']['properties']));
    }

    public function testSortStableWithoutSortAllKeysReturnsTheSpecUnchanged(): void
    {
        $spec = $this->buildSpec();

        $this->assertSame($spec, OpenApiSpecProcessor::sortStable($spec));
    }

    private function buildSpec(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Studio Backend API', 'version' => '1.0'],
            'paths' => [
                '/pimcore-studio/api/assets/{id}' => [
                    'get' => [
                        'tags' => ['Assets'],
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Asset'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/pimcore-studio/api/bundle/data-hub/config' => [
                    'get' => [
                        'tags' => ['Bundle Data Hub'],
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/DataHubConfig'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/pimcore-studio/api/bundle/data-hub' => [
                    'get' => ['tags' => ['Bundle Data Hub'], 'responses' => []],
                ],
                '/pimcore-studio/api/bundle/data-hub-file-export/config' => [
                    'get' => ['tags' => ['Bundle Data Hub File Export'], 'responses' => []],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Asset' => ['type' => 'object'],
                    'DataHubConfig' => [
                        'type' => 'object',
                        'properties' => ['error' => ['$ref' => '#/components/schemas/Error']],
                    ],
                    'Error' => [
                        'type' => 'object',
                        'properties' => ['dev' => ['$ref' => '#/components/schemas/DevError']],
                    ],
                    'DevError' => ['type' => 'object'],
                    'Unreferenced' => ['type' => 'object'],
                ],
                'securitySchemes' => [
                    'auth' => ['type' => 'http'],
                ],
            ],
            'tags' => [
                ['name' => 'Assets'],
                ['name' => 'Bundle Data Hub'],
                ['name' => 'Bundle Data Hub File Export'],
            ],
        ];
    }
}
