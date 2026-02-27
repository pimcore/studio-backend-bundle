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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\SelectOptions;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\UpdateSelectOption;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\SelectOptionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/class/select-option/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SelectOptionServiceInterface $selectOptionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException|NotFoundException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_select_option_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::SELECT_OPTIONS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_select_option_update',
        description: 'class_select_option_update_description',
        summary: 'class_select_option_update_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'id',
        example: 'EventStatus',
        description: 'Select option configuration ID',
        required: true
    )]
    #[ReferenceRequestBody(UpdateSelectOption::class)]
    #[SuccessResponse(
        description: 'class_select_option_update_success_response',
        content: new JsonContent(ref: SelectOptionDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateSelectOption(
        string $id,
        #[MapRequestPayload] UpdateSelectOptionParameters $parameters,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->selectOptionService->updateSelectOption($id, $parameters)
        );
    }
}
