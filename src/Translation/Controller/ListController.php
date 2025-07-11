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
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translations;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/translations/list';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_list', methods: ['POST'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_get_list',
        description: 'translation_get_list_description',
        summary: 'translation_get_list_summary',
        tags: [Tags::Translation->name]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
        '{"key":"de", "type":"like", "filterValue": "%text%"}'
        . ']',
        sortFilterExample: '{"key":"de", "direction":"ASC"}'
    )]
    #[TextFieldParameter(
        name: 'domain',
        description: 'Domain to filter translations by',
        example: 'de'
    )]
    #[SuccessResponse(
        description: 'translation_get_list_success_response',
        content: new CollectionJson(new GenericCollection(Translations::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getList(
        #[MapRequestPayload] CollectionFilterParameter $parameters,
        #[MapQueryParameter] string $domain = TranslatorServiceInterface::DOMAIN
    ): JsonResponse {
        $collection = $this->translatorService->listTranslations(
            $domain,
            $parameters,
        );

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
