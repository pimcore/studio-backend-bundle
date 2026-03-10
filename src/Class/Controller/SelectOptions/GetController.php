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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\SelectOptionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const string ROUTE = '/class/select-option/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SelectOptionServiceInterface $selectOptionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_select_option_get', methods: ['GET'])]
    #[IsGranted(UserPermissions::SELECT_OPTIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_select_option_get',
        description: 'class_select_option_get_description',
        summary: 'class_select_option_get_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'id',
        example: 'EventStatus',
        description: 'Select option configuration ID',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_select_option_get_success_response',
        content: new JsonContent(ref: SelectOptionDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getSelectOption(string $id): JsonResponse
    {
        return $this->jsonResponse(
            $this->selectOptionService->getSelectOption($id)
        );
    }
}
