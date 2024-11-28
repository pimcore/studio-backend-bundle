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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Response\AvailableSitesJson;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\ExcludeMainSiteParameter as MappedParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\OpenApi\Attribute\Parameter\Query\ExcludeMainSiteParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\SiteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AvailableSitesController extends AbstractApiController
{
    public function __construct(
        private readonly SiteServiceInterface $siteService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/documents/sites/list-available',
        name: 'pimcore_studio_api_sites_list_available',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . '/documents/sites/list-available',
        operationId: 'documents_list_available_sites',
        description: 'documents_list_available_sites_description',
        summary: 'documents_list_available_sites_summary',
        tags: [Tags::Documents->value]
    )]
    #[ExcludeMainSiteParameter]
    #[SuccessResponse(
        description: 'documents_list_available_sites_success_response',
        content: new AvailableSitesJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getAvailableSites(
        #[MapQueryString] MappedParameter $parameter
    ): JsonResponse {
        return $this->jsonResponse(['items' => $this->siteService->getAvailableSites($parameter)]);
    }
}
