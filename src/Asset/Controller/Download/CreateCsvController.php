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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Download;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request\CsvExportRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateCsvController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/csv/create', name: 'pimcore_studio_api_create_csv_asset', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::API_PATH . '/assets/csv/create',
        operationId: 'asset_create_csv',
        description: 'asset_create_csv_description',
        summary: 'asset_create_csv_summary',
        tags: [Tags::Assets->name]
    )]
    #[CsvExportRequestBody]
    #[CreatedResponse(
        description: 'asset_create_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function assetCreateCsv(
        #[MapRequestPayload] ExportAssetParameter $exportAssetParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->csvService->generateCsvFile($exportAssetParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
