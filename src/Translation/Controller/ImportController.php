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

use JsonException;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\FileUpload;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Property\CsvSettingsProperty;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\CsvSettingsParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\DeltaItem;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\ImportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ImportController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/translations/{domain}/import';

    public function __construct(
        SerializerInterface $serializer,
        private readonly DenormalizerInterface $denormalizer,
        private readonly ImportServiceInterface $importService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|ForbiddenException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_import', methods: ['POST'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_import_csv',
        description: 'translation_import_csv_description',
        summary: 'translation_import_csv_summary',
        tags: [Tags::Translation->name]
    )]
    #[StringParameter(name: 'domain', example: 'studio', description: 'Domain of the translation for import')]
    #[MultipartFormDataRequestBody(
        properties: [
            new FileUpload(description: 'CSV import file to upload'),
            new CsvSettingsProperty(),
        ],
        required: ['file', 'csvSettings']
    )]
    #[SuccessResponse(
        description: 'translation_import_csv_success_response',
        content: new ItemsJson(DeltaItem::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function importTranslations(
        string $domain,
        // TODO: Symfony 7.1 change to https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute
        // TODO: Remove manual extraction of csvSettings from request body and use attribute when available
        Request $request,
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new EnvironmentException('Invalid file found in the request');
        }

        // Manually extract csvSettings from multipart form data
        $csvSettings = $request->request->getString('csvSettings');

        if (empty($csvSettings)) {
            throw new EnvironmentException('Invalid csvSettings found in the request');
        }

        try {
            $csvSettings = json_decode($csvSettings, true, 512, JSON_THROW_ON_ERROR);
            $parameters = $this->denormalizer->denormalize($csvSettings, CsvSettingsParameter::class);
        } catch (JsonException|ExceptionInterface $e) {
            throw new EnvironmentException('Failed to parse csvSettings: ' . $e->getMessage());
        }

        return $this->jsonResponse(['items' => $this->importService->import($file, $domain, $parameters)]);
    }
}
