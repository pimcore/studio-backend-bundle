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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Controller\Redirect;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RedirectsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/bundle/seo/redirects';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RedirectsServiceInterface $redirectsService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_redirects_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::REDIRECTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_redirects_get_collection',
        description: 'bundle_seo_redirects_get_collection_description',
        summary: 'bundle_seo_redirects_get_collection_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{"type":"search", "filterValue": "https://www.some-site/old-url"},' .
            '{"key":"source", "type":"url", "filterValue": "https://www.some-example"},' .
            '{"key":"source", "type":"like", "filterValue": "en/news"},' .
            '{"key":"target", "type":"equals", "filterValue": "en/news/new"}'
        . ']',
        sortFilterExample: '{"key":"priority", "direction":"ASC"}'
    )]
    #[SuccessResponse(
        description: 'bundle_seo_redirects_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(Redirect::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getRedirects(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $collection = $this->redirectsService->listRedirects($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
