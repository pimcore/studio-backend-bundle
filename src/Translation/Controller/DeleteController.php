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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter as QueryStringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/translations/{key}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_delete_translation', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_delete_by_key',
        description: 'translation_delete_by_key_description',
        summary: 'translation_delete_by_key_summary',
        tags: [Tags::Translation->name]
    )]
    #[StringParameter('key', 'some_key', description: 'Delete translations by matching key')]
    #[QueryStringParameter(
        name: 'domain',
        example: 'studio',
        description: 'Domain of the translation, defaults to "studio"',
        required: false
    )]
    #[SuccessResponse(
        description: 'translation_delete_by_key_success_description',
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function deleteTranslation(
        string $key,
        #[MapQueryParameter] string $domain = TranslatorServiceInterface::DOMAIN
    ): Response {
        $this->translatorService->deleteTranslationByKey($key, $domain);

        return new Response();
    }
}
