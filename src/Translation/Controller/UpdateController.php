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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Request\TranslationUpdateBody;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/translations/{domain}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidLocaleException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::TRANSLATIONS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_update',
        description: 'translation_update_description',
        summary: 'translation_update_summary',
        tags: [Tags::Translation->name]
    )]
    #[TranslationUpdateBody]
    #[StringParameter(name: 'domain', example: 'studio', description: 'Domain of the translation, to be updated')]
    #[SuccessResponse(
        description: 'translation_update_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateTranslations(
        string $domain,
        #[MapRequestPayload] UpdateParameter $parameter
    ): Response {
        $this->translatorService->updateTranslations($domain, $parameter);

        return new Response();
    }
}
