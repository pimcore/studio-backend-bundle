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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Attributes\Request\BlocklistRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailAddressParameter;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\BlocklistServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AddController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly BlocklistServiceInterface $blocklistService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     */
    #[Route('/emails/blocklist', name: 'pimcore_studio_api_emails_blocklist_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Post(
        path: self::API_PATH . '/emails/blocklist',
        operationId: 'addBlocklistEntry',
        summary: 'Add a new blocklist entry.',
        tags: [Tags::Emails->value]
    )]
    #[BlocklistRequestBody]
    #[SuccessResponse(
        description: 'Successfully added a new blocklist entry',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function addBlocklistEntry(
        #[MapRequestPayload] EmailAddressParameter $request
    ): Response {
        $this->blocklistService->addEntry($request->getEmail());

        return new Response();
    }
}
