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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\BlocklistServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
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
    public function __construct(
        SerializerInterface $serializer,
        private readonly BlocklistServiceInterface $blocklistService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     * @throws NotFoundException
     */
    #[Route('/emails/blocklist', name: 'pimcore_studio_api_emails_blocklist_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Delete(
        path: self::API_PATH . '/emails/blocklist',
        operationId: 'email_blocklist_delete',
        description: 'Delete blocklist entry based on the provided email address',
        summary: 'Delete blocklist entry',
        tags: [Tags::Emails->value]
    )]
    #[TextFieldParameter(
        name: 'email',
        description: ' Email address to be deleted from blocklist',
        required: false
    )]
    #[SuccessResponse(
        description: 'Successfully deleted blocklist entry',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteBlocklistEntry(#[MapQueryParameter] string $email): Response
    {
        $this->blocklistService->deleteEntry($email);

        return new Response();
    }
}
