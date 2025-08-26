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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\ExportRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/translations/export';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_export', methods: ['POST'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_export_list',
        description: 'translation_export_list_description',
        summary: 'translation_export_list_summary',
        tags: [Tags::Translation->name]
    )]
    #[ExportRequestBody(
        columnFiltersExample: '[' .
        '{"key":"de", "type":"translationLike", "filterValue": "%car%"},' .
        '{"type":"search", "filterValue": "search term"}'
        . ']',
        sortFilterExample: '{"key":"key", "direction":"ASC"}'
    )]
    #[TextFieldParameter(
        name: 'domain',
        description: 'Domain to filter translations by',
        example: 'studio'
    )]
    #[SuccessResponse(
        description: 'translation_export_list_success_response',
        content: [new MediaType(MimeTypes::CSV->value)],
        headers: [new ContentDisposition(fileName: 'studio_translations.csv')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function exportTranslations(
        #[MapRequestPayload] CollectionFilterParameter $parameters,
        #[MapQueryParameter] string $domain = TranslatorServiceInterface::DOMAIN
    ): Response {

        return $this->csvService->export($domain, $parameters);
    }
}
