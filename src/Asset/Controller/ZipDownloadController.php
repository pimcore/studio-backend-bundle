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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DownloadIdsParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ZipDownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Content\ScalarItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
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
final class ZipDownloadController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ZipDownloadServiceInterface $zipDownloadService
    )
    {
        parent::__construct($serializer);
    }

    #[Route('/assets/create-zip-download', name: 'pimcore_studio_api_create_zip_download_asset', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::API_PATH . '/assets/create-zip-download',
        operationId: 'createZipDownloadAssets',
        description: 'Creating zipped assets',
        summary: 'Creating zip file for assets',
        tags: [Tags::Assets->name]
    )]
    #[RequestBody(
        content: new ScalarItemsJson('integer')
    )]
    #[SuccessResponse(
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'path',
                    description: 'Path to the zip file',
                    type: 'string',
                    example: '/var/www/html/var/assets.zip'
                )
            ]
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function createZipDownloadAssets(#[MapRequestPayload] DownloadIdsParameter $downloadIds): Response
    {
        return $this->jsonResponse(['path' => $this->zipDownloadService->generateZipFile($downloadIds)]);
    }
}
