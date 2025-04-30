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
        path: self::PREFIX . '/logout',
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
