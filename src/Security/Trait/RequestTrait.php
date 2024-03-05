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

namespace Pimcore\Bundle\StudioApiBundle\Security\Trait;

use Pimcore\Bundle\StudioApiBundle\Exception\NoAuthTokenFound;
use Pimcore\Bundle\StudioApiBundle\Exception\NoRequestException;
use Symfony\Component\HttpFoundation\Request;

trait RequestTrait
{
    private const BEARER_PREFIX = 'Bearer ';

    private const AUTHORIZATION_HEADER = 'Authorization';

    private function getAuthToken(Request $request): string
    {
        $authToken = $request->headers->get(self::AUTHORIZATION_HEADER);
        if($authToken === null) {
            throw new NoAuthTokenFound('Full authentication is required to access this resource.');
        }

        return $this->removeBearerPrefix($authToken);
    }

    private function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if(!$request) {
            throw new NoRequestException('No request found');
        }

        return $request;
    }

    private function removeBearerPrefix(string $token): string
    {
        return str_replace(self::BEARER_PREFIX, '', $token);
    }
}
