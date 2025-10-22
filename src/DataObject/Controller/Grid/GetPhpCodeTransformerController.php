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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetPhpCodeTransformerController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PhpCodeTransformerCollectorInterface $collector,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/data-objects/grid/transformers/services/phpcode',
        name: 'pimcore_studio_api_get_phpcode_transformers',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/data-objects/grid/transformers/services/phpcode',
        operationId: 'data_object_get_phpcode_transformers',
        description: 'data_object_get_phpcode_transformers_description',
        summary: 'data_object_get_phpcode_transformers_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[SuccessResponse(
        description: 'data_object_get_phpcode_transformers_success_response',
        content: new JsonContent(
            properties: [
                new Property(property: 'transformers', type: 'array', items: new Items(
                    properties: [
                        new Property(property: 'key', type: 'string'),
                        new Property(property: 'label', type: 'string'),
                        new Property(property: 'description', type: 'string'),
                    ]
                )),
            ]
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getPhpCodeTransformers(): JsonResponse
    {
        return $this->jsonResponse([
            'transformers' => $this->collector->collect(),
        ]);
    }
}
