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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use Pimcore\Bundle\StudioBackendBundle\Exception\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotAuthorizedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
        if($authToken === null) {
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

        if(!$request) {
            throw new NoRequestException();
        }

        return $request;
    }

    private function removeBearerPrefix(string $token): string
    {
        return str_replace(self::BEARER_PREFIX, '', $token);
    }
}
