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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnreachableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class LogoutController extends AbstractApiController
{
    #[Route('/logout', name: 'pimcore_studio_api_logout', methods: ['POST'])]
    #[Post(
        path: self::API_PATH . '/logout',
        operationId: 'logout',
        description: 'logout_description',
        summary: 'logout_summary',
        tags: [Tags::Authorization->name]
    )]
    #[SuccessResponse(
        description: 'logout_success_response',
    )]
    public function logout(): void
    {
        throw new UnreachableException('Should not be called. Handled by symfony.');
    }
}
