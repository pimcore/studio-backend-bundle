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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller;

use Exception;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception|NotFoundException
     */
    #[Route(
        '/class/{dataObjectClass}',
        name: 'pimcore_studio_api_class_get_by_data_object_class',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/class/{dataObjectClass}',
        operationId: 'class_get_by_data_object_class',
        description: 'class_get_by_data_object_class_description',
        summary: 'class_get_by_data_object_class_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'dataObjectClass',
        example: 'CAR',
        description: 'class_get_by_data_object_class_data_object_class',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(ClassDefinition::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getClassDefinition(string $dataObjectClass): JsonResponse
    {
        return $this->jsonResponse(
            $this->classDefinitionService->getClassDefinition($dataObjectClass)
        );
    }
}
