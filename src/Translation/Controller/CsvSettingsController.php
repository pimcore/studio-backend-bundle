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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Request\CsvSampleRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\CsvSampleParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CsvSettings;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CsvSettingsController extends AbstractApiController
{
    private const string ROUTE = '/translations/csv-settings';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_csv_settings', methods: ['POST'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_determine_csv_settings_for_import',
        description: 'translation_determine_csv_settings_for_import_description',
        summary: 'translation_determine_csv_settings_for_import_summary',
        tags: [Tags::Translation->name]
    )]
    #[CsvSampleRequestBody]
    #[SuccessResponse(
        description: 'translation_determine_csv_settings_for_import_success_response',
        content: new JsonContent(ref: CsvSettings::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function determineCsvSettings(#[MapRequestPayload] CsvSampleParameter $parameter): JsonResponse
    {
        
        return $this->jsonResponse($this->csvService->determineCsvDialect($parameter->getSample()));
    }
}