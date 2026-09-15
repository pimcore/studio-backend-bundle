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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\AuthorizationConsentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Details of a pending authorization for the Studio UI consent screen: which
 * client is asking, for which scopes, and which user would be acting.
 *
 * @internal
 */
final class AuthorizationDetailsController extends AbstractApiController
{
    private const string ROUTE = '/oauth/authorizations/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly AuthorizationConsentServiceInterface $authorizationConsentService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_oauth_authorization_details', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'oauth_authorization_details',
        description: 'oauth_authorization_details_description',
        summary: 'oauth_authorization_details_summary',
        tags: [Tags::Oauth->value],
    )]
    #[StringParameter('id', 'a1b2c3', 'Opaque id of the pending authorization')]
    #[SuccessResponse(
        description: 'oauth_authorization_details_success_response',
        content: new JsonContent(ref: AuthorizationConsent::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function __invoke(string $id): Response
    {
        return $this->jsonResponse($this->authorizationConsentService->getConsent($id));
    }
}
