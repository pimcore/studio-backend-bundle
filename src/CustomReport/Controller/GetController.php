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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Controller;

use Exception;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeNode;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportServiceInterface $customReportService,
        private readonly CustomReportHydratorInterface $customReportHydrator

    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException
     * @throws Exception
     */
    #[Route('/custom-reports/{name}',
        name: 'pimcore_studio_api_custom_report_get',
        methods: ['GET'])
    ]
    //TODO Permissions
    //    #[IsGranted(
    //        'hasOneOf([
    //            \\Pimcore\\Bundle\\StudioBackendBundle\\Util\Constant\\CustomReportPermissions::REPORTS->value,
    //            \\Pimcore\\\Bundle\\StudioBackendBundle\\Util\\Constant\\CustomReportPermissions::REPORTS_CONFIG->value
    //        ])')]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Get(
        path: self::PREFIX . '/custom-reports/{name}',
        operationId: 'custom_report_get_by_name',
        summary: 'custom_report_get_by_name_summary',
        tags: [Tags::CustomReports->value]
    )]
    #[NameParameter(
        name: 'name',
        description: 'custom_report_get_by_name_name_parameter',
        example: 'Quality_Attributes'
    )
    ]
    //TODO schema
    #[SuccessResponse(
        description: 'custom_report_get_by_name_success_response',
        content: new JsonContent(ref: CustomReportTreeNode::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getByName(string $name): JsonResponse
    {
        $config = $this->customReportService->getCustomReportByName($name);
        if (!$config) {
            throw new NotFoundException('Custom report', $name, 'name');
        }

        return $this->jsonResponse(
            $this->customReportHydrator->hydrateCustomReportDetails($config)
        );
    }
}
