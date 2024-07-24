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
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Content\ScalarItemsJson;
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
final class CreateZipController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ZipServiceInterface $zipService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException|StreamResourceNotFoundException
     */
    #[Route('/assets/zip/create', name: 'pimcore_studio_api_create_zip_asset', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::API_PATH . '/assets/zip/create',
        operationId: 'createZipAssets',
        description: 'Creating zipped assets',
        summary: 'Creating zip file for assets',
        tags: [Tags::Assets->name]
    )]
    #[RequestBody(
        content: new ScalarItemsJson('integer')
    )]
    #[CreatedResponse(
        description: 'Successfully created jobRun for zip export',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function createZippedAssets(
        #[MapRequestPayload] CreateAssetFileParameter $createAssetFileParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->zipService->generateZipFile($createAssetFileParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
