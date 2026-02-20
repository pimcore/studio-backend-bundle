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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\FieldCollection;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\FieldCollectionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    private const string ROUTE = '/class/field-collection/{key}/export';

    public function __construct(
        SerializerInterface $serializer,
        private readonly FieldCollectionServiceInterface $fieldCollectionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_field_collection_export',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::FIELD_COLLECTIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_field_collection_export',
        description: 'class_field_collection_export_description',
        summary: 'class_field_collection_export_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'key',
        example: 'MyFieldCollection',
        description: 'Field collection unique key',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_field_collection_export_success_response',
        content: [new MediaType(MimeTypes::JSON->value)],
        headers: [new ContentDisposition(fileName: 'fieldcollection_MyFieldCollection_export.json')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function exportFieldCollection(string $key): Response
    {
        return $this->fieldCollectionService->exportFieldCollection($key);
    }
}
