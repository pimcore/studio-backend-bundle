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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Country;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class CountryCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/settings/available-countries';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SettingsServiceInterface $settingsService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_settings_country_collection',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'settings_country_collection',
        description: 'settings_country_collection_description',
        summary: 'settings_country_collection_summary',
        tags: [Tags::Settings->value],
    )]
    #[SuccessResponse(
        description: 'settings_country_collection_success_response',
        content: new CollectionJson(new GenericCollection(Country::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCountryCollection(): JsonResponse
    {
        $countries = $this->settingsService->getAvailableCountries();

        return $this->getPaginatedCollection(
            $this->serializer,
            $countries,
            count($countries)
        );
    }
}

