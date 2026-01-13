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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataProvider;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Returns the list of available GDPR providers.
 *
 * @internal
 */
final class GetDataProviderController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly GdprManagerServiceInterface $gdprManagerService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/gdpr/providers',
        name: 'pimcore_studio_api_gdpr_providers',
        methods: ['GET'])]
    #[IsGranted(UserPermissions::GDPR->value)]
    #[Get(
        path: self::PREFIX . '/gdpr/providers',
        operationId: 'gdpr_list_providers',
        description: 'gdpr_list_providers_description',
        summary: 'gdpr_list_providers_summary',
        tags: [Tags::GDPR->value]
    )]
    #[SuccessResponse(
        description: 'gdpr_list_providers_success_response',
        content: new CollectionJson(new GenericCollection(GdprDataProvider::class))
    )]

    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
    ])]
    public function getProvidersList(): JsonResponse
    {
        $collection = $this->gdprManagerService->getAvailableProviders();

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
