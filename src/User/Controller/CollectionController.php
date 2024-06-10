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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserListParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserTreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService
    ) {
        parent::__construct($serializer);
    }


    #[Route('/users', name: 'pimcore_studio_api_users', methods: ['GET'])]
    #[Get(
        path: self::API_PATH . '/users',
        operationId: 'getUsers',
        summary: 'Get collection of users for tree view',
        tags: [Tags::User->value]
    )]
    #[ParentIdParameter(
        description: 'Filter users by parent id.',
        required: true,
        minimum: 0,
        example: 0
    )]
    #[SuccessResponse(
        description: 'Collection of users including folders for the given parent id.',
        content: new CollectionJson(new GenericCollection(UserTreeNode::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND
    ])]
    public function getUsers(#[MapQueryString] UserListParameter $userList): Response
    {
        $users = $this->userService->getUserTreeListing($userList);
        return $this->getPaginatedCollection($this->serializer, $users->getItems(), $users->getTotalItems());
    }
}
