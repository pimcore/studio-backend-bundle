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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\OpenApi\Fixtures;

use OpenApi\Attributes as OA;

/**
 * Scanned by swagger-php in OpenApiServiceTest — never autoloaded or executed.
 * The tag declarations are deliberately out of order to prove the deterministic
 * tag sort.
 *
 * @internal
 */
#[OA\OpenApi(openapi: '3.1.0')]
#[OA\Info(version: '1.0.0', title: 'Fixture API')]
#[OA\Tag(name: 'Bundle Sample', description: 'bundle_sample_tag_description')]
#[OA\Tag(name: 'Fixtures', description: 'fixtures_tag_description')]
final class FixtureApiPart
{
    #[OA\Get(
        path: '{prefix}/fixtures/{id}',
        operationId: 'fixture_get_by_id',
        summary: 'fixture_get_by_id_summary',
        tags: ['Fixtures'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'fixture_get_by_id_success_description',
                content: new OA\JsonContent(ref: '#/components/schemas/Fixture')
            ),
        ]
    )]
    public function getById(): void
    {
    }

    #[OA\Get(
        path: '{prefix}/bundle/sample/config',
        operationId: 'bundle_sample_get_config',
        summary: 'bundle_sample_get_config_summary',
        tags: ['Bundle Sample'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'bundle_sample_get_config_success_description',
                content: new OA\JsonContent(ref: '#/components/schemas/SampleConfig')
            ),
        ]
    )]
    public function getConfig(): void
    {
    }

    #[OA\Get(
        path: '{prefix}/bundle/sample-other/config',
        operationId: 'bundle_sample_other_get_config',
        summary: 'bundle_sample_other_get_config_summary',
        tags: ['Bundle Sample'],
        responses: [
            new OA\Response(response: 200, description: 'bundle_sample_other_success_description'),
        ]
    )]
    public function getOtherConfig(): void
    {
    }
}

#[OA\Schema(schema: 'Fixture', title: 'Fixture', type: 'object')]
final class FixtureSchema
{
    #[OA\Property(property: 'name', type: 'string')]
    public string $name = '';
}

#[OA\Schema(schema: 'SampleConfig', title: 'SampleConfig', type: 'object')]
final class SampleConfigSchema
{
    #[OA\Property(property: 'error', ref: '#/components/schemas/Fixture')]
    public object $error;
}
