<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SearchUserController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException
     */
    #[Route('/user/search', name: 'pimcore_studio_api_user_search', methods: ['GET'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Get(
        path: self::PREFIX . '/user/search',
        operationId: 'pimcore_studio_api_user_search',
        summary: 'user_search_summary',
        tags: [Tags::User->value]
    )]
    #[TextFieldParameter(
        name: 'searchQuery',
        description: 'Query to search for an user. This can be a part of username, firstname, lastname, email or id.',
        required: false
    )]
    #[SuccessResponse(
        description: 'user_search_summary_response',
        content: new CollectionJson(new GenericCollection(SimpleUser::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function getUserSearch(#[MapQueryParameter] string $searchQuery): JsonResponse
    {
        return $this->jsonResponse(
            $this->userService->userSearch($searchQuery)
        );
    }
}
