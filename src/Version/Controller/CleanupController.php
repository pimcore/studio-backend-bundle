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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\TimestampParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\NotFoundResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Version\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionCleanupParameters;
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
        private readonly RepositoryInterface $repository,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/versions', name: 'pimcore_studio_api_cleanup_versions', methods: ['DELETE'])]
    //#[IsGranted('STUDIO_API')]
    #[Delete(
        path: self::API_PATH . '/versions',
        operationId: 'cleanupVersion',
        description: 'Cleanup versions based on the provided parameters',
        summary: 'Cleanup versions',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Versions->name]
    )]
    #[IdParameter('ID of the element', 'element')]
    #[ElementTypeParameter]
    #[TimestampParameter('elementModificationDate', 'Modification timestamp of the element', true)]
    #[SuccessResponse(
        description: 'IDs of deleted versions',
        content: new IdsJson('IDs of deleted versions')
    )]
    #[UnauthorizedResponse]
    #[NotFoundResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function cleanupVersions(#[MapQueryString] VersionCleanupParameters $parameters): JsonResponse
    {
        return $this->jsonResponse(['ids' => $this->repository->cleanupVersions($parameters)]);
    }
}
