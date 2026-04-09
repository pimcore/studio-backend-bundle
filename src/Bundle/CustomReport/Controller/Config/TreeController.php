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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Controller\Config;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Response\Property\AnyOfCustomReportNodes;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\TreeParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class TreeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/bundle/custom-reports/tree/config';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportServiceInterface $customReportService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_tree_config', methods: ['GET'])]
    #[IsGranted(CustomReportPermissions::REPORTS_CONFIG->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_config_get_tree',
        description: 'custom_reports_config_get_tree_description',
        summary: 'custom_reports_config_get_tree_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[BoolParameter(
        'withGroup',
        description: 'Whether to group the results by report group.',
        required: false,
        example: false
    )]
    #[SuccessResponse(
        description: 'custom_reports_config_get_tree_success_response',
        content: new CollectionJson(new AnyOfCustomReportNodes())
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCustomReports(
        #[MapQueryString] TreeParameter $parameters = new TreeParameter()
    ): JsonResponse {
        $items = $this->customReportService->getCustomReportConfigTree($parameters->isWithGroup());

        return $this->getPaginatedCollection($this->serializer, $items, count($items));
    }
}
