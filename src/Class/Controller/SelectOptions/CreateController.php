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
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\CreateSelectOption;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\SelectOptionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
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
final class CreateController extends AbstractApiController
{
    private const string ROUTE = '/class/select-option';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SelectOptionServiceInterface $selectOptionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException|ElementSavingFailedException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_select_option_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::SELECT_OPTIONS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_select_option_create',
        description: 'class_select_option_create_description',
        summary: 'class_select_option_create_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[ReferenceRequestBody(CreateSelectOption::class)]
    #[SuccessResponse(
        description: 'class_select_option_create_success_response',
        content: new JsonContent(ref: SelectOptionDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createSelectOption(
        #[MapRequestPayload] CreateSelectOptionParameters $parameters,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->selectOptionService->createSelectOption($parameters)
        );
    }
}
