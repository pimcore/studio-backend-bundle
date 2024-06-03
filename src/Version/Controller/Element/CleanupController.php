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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Controller\Element;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\TimestampParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionCleanupParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CleanupController extends AbstractApiController
{
    public function __construct(
        private readonly VersionServiceInterface $versionService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/versions/{elementType}/{id}', name: 'pimcore_studio_api_cleanup_versions', methods: ['DELETE'])]
    //#[IsGranted('STUDIO_API')]
    #[Delete(
        path: self::API_PATH . '/versions/{elementType}/{id}',
        operationId: 'cleanupVersion',
        description: 'Cleanup versions based on the provided parameters',
        summary: 'Cleanup versions',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Versions->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter('ID of the element')]
    #[TimestampParameter('elementModificationDate', 'Modification timestamp of the element', true)]
    #[SuccessResponse(
        description: 'IDs of deleted versions',
        content: new IdsJson('IDs of deleted versions')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cleanupVersions(
        string $elementType,
        int $id,
        #[MapQueryString] VersionCleanupParameters $parameters
    ): JsonResponse
    {
        return $this->jsonResponse(
            ['ids' => $this->versionService->cleanupVersions(new ElementParameters($elementType, $id), $parameters)]
        );
    }
}
