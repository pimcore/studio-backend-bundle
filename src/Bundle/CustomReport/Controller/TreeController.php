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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    private const string ROUTE = '/bundle/custom-reports/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportConfigServiceInterface $customReportConfigService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidQueryTypeException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_tree', methods: ['GET'])]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_get_tree',
        description: 'custom_reports_get_tree_description',
        summary: 'custom_reports_get_tree_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[SuccessResponse(
        description: 'custom_reports_collection_success_response',
        content: new ItemsJson(CustomReportTreeNode::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCustomReports(): JsonResponse
    {
        $items = $this->customReportConfigService->getCustomReportTree();

        return $this->getPaginatedCollection($this->serializer, $items, count($items));
    }
}
