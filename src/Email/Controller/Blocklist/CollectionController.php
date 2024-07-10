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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Controller\Blocklist;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Attributes\Response\Property\BlocklistCollection;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\BlocklistRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly BlocklistRepositoryInterface $blocklistRepository,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route('/emails/blocklist', name: 'pimcore_studio_api_emails_blocklist_list', methods: ['GET'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Get(
        path: self::API_PATH . '/emails/blocklist',
        operationId: 'getBlocklistEntries',
        description: 'Get paginated blocklist entries',
        summary: 'Get all blocklist entries',
        tags: [Tags::Emails->name]
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[TextFieldParameter(
        name: 'email',
        description: 'Email address to be filtered by',
        required: false
    )]
    #[SuccessResponse(
        description: 'Paginated blocklist entries with total count as header param',
        content: new CollectionJson(new BlocklistCollection())
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getBlocklistEntries(
        #[MapQueryString] CollectionParameters $parameters,
        #[MapQueryParameter] ?string $email = null
    ): JsonResponse
    {
        $collection = $this->blocklistRepository->listEntries($parameters, $email);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
