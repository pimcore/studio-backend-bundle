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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\PreviewUrlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IntParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\RedirectResponse as RedirectResponseAttribute;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class PreviewController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PreviewUrlServiceInterface $previewUrlService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException
     */
    #[Route('/data-objects/preview/{id}', name: 'pimcore_studio_api_data_objects_preview', methods: ['GET'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/data-objects/preview/{id}',
        operationId: 'data_object_preview_by_id',
        description: 'data_object_preview_by_id_description',
        summary: 'data_object_preview_by_id_summary',
        tags: [Tags::DataObjects->value]
    )]
    #[IdParameter(type: 'data object')]
    #[IntParameter(
        name: 'site',
        description: 'Site ID for multi-site setups',
        required: false,
        example: 0,
    )]
    #[RedirectResponseAttribute(description: 'data_object_preview_by_id_success_response')]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::REDIRECT,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function preview(int $id, Request $request): RedirectResponse
    {
        $queryParams = $request->query->all();
        $site = (int) ($queryParams['site'] ?? 0);
        unset($queryParams['site']);

        return new RedirectResponse(
            $this->previewUrlService->getPreviewUrl(new PreviewParameter($id, $site), $queryParams)
        );
    }
}
