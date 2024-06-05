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

use Exception;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attributes\Response\LogoutSuccessful;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
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
        summary: 'Logout and invalidate current session for active user',
        tags: [Tags::Authorization->name]
    )]
    #[SuccessResponse(
        description: 'Logout successful',
    )]
    public function logout(): void
    {
        throw new Exception('Should not be called. Handled by symfony.');
    }
}