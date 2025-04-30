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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @internal
 */
trait RequestTrait
{
    private const BEARER_PREFIX = 'Bearer ';

    private const AUTHORIZATION_HEADER = 'Authorization';

    /**
     * @throws NotAuthorizedException
     */
    private function getAuthToken(Request $request): string
    {
        $authToken = $request->headers->get(self::AUTHORIZATION_HEADER);
        if ($authToken === null) {
            throw new NotAuthorizedException();
        }

        return $this->removeBearerPrefix($authToken);
    }

    /**
     * @throws NoRequestException
     */
    private function getCurrentRequest(RequestStack $requestStack): Request
    {
        $request = $requestStack->getCurrentRequest();

        if (!$request) {
            throw new NoRequestException();
        }

        return $request;
    }

    private function getCurrentSession(RequestStack $requestStack): SessionInterface
    {
        return $this->getCurrentRequest($requestStack)->getSession();
    }

    private function removeBearerPrefix(string $token): string
    {
        return str_replace(self::BEARER_PREFIX, '', $token);
    }
}
