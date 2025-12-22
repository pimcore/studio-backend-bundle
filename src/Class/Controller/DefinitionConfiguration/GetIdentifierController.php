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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\DefinitionConfiguration;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionIdentifierData;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\IdentifierServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class GetIdentifierController extends AbstractApiController
{
    private const string ROUTE = '/class/definition/configuration-view/identifier-data';

    public function __construct(
        SerializerInterface $serializer,
        private readonly IdentifierServiceInterface $identifierService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_definition_get_identifier_data',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_definition_get_identifier_data',
        description: 'class_definition_get_identifier_data_description',
        summary: 'class_definition_get_identifier_data_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[SuccessResponse(
        description: 'class_definition_get_identifier_data_success_response',
        content: new JsonContent(ref: ClassDefinitionIdentifierData::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getClassDefinitionIdentifierData(): JsonResponse
    {
        return $this->jsonResponse(
            $this->identifierService->getClassIdentifierData()
        );
    }
}
