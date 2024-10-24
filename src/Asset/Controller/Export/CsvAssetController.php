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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Export;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\CsvExportAssetRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CsvAssetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/export/csv/asset', name: 'pimcore_studio_api_asset_export_csv_asset', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/assets/export/csv/asset',
        operationId: 'asset_export_csv_asset',
        description: 'asset_export_csv_asset_description',
        summary: 'asset_export_csv_asset_summary',
        tags: [Tags::Assets->name]
    )]
    #[CsvExportAssetRequestBody]
    #[CreatedResponse(
        description: 'asset_export_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function assetExportCsvAsset(
        #[MapRequestPayload] ExportAssetParameter $exportAssetParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->csvService->generateCsvFileForAssets($exportAssetParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
