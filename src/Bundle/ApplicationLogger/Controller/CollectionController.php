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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service\LogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
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

    private const string ROUTE = '/bundle/application-logger/list';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LogServiceInterface $logService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_application_logger_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::APPLICATION_LOGGING->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_application_logger_get_collection',
        description: 'bundle_application_logger_get_collection_description',
        summary: 'bundle_application_logger_get_collection_summary',
        tags: [Tags::BundleApplicationLogger->value]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{' .
                '"key":"dateFrom",' .
                '"type":"date",' .
                '"filterValue":{"operator": "from", "value": "2025-06-25T10:00:00+02:00"}' .
            '},' .
            '{' .
                '"key":"dateTo",' .
                '"type":"date",' .
                '"filterValue":{"operator": "to", "value": "2025-06-30T24:00:00+02:00"}' .
            '},' .
            '{"key":"priority", "type":"equals", "filterValue": 2},' .
            '{"key":"pid", "type":"like", "filterValue": 44}'
            . ']',
        sortFilterExample: '{"key":"id", "direction":"DESC"}'
    )]
    #[SuccessResponse(
        description: 'bundle_application_logger_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(LogEntry::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getLogs(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $collection = $this->logService->listLogEntries($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
