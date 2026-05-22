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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\MappedParameter\FilePathParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service\LogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class FileObjectController extends AbstractApiController
{
    private const string ROUTE = '/bundle/application-logger/file-object';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LogServiceInterface $logService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|StreamResourceNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_application_logger_file_object', methods: ['GET'])]
    #[IsGranted(UserPermissions::APPLICATION_LOGGING->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_application_logger_get_file_object',
        description: 'bundle_application_logger_get_file_object_description',
        summary: 'bundle_application_logger_get_file_object_summary',
        tags: [Tags::BundleApplicationLogger->value]
    )]
    #[TextFieldParameter(
        name: 'filePath',
        description: 'Path to the file object in the application log storage',
        required: true,
        example: '/2025/01/01/log-entry.log'
    )]
    #[SuccessResponse(
        description: 'bundle_application_logger_get_file_object_success_response',
        content: [new MediaType('text/plain')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getFileObject(#[MapQueryString] FilePathParameter $parameter): StreamedResponse
    {
        return $this->logService->streamFileObject($parameter->getFilePath());
    }
}
