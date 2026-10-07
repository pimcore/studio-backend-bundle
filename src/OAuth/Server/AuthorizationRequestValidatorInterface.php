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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;

/**
 * @internal
 */
interface AuthorizationRequestValidatorInterface
{
    /**
     * Re-validates stored authorization parameters into a league authorization request, or
     * null when they no longer describe a valid one.
     *
     * @param array<string, mixed> $queryParams
     *
     * @throws MissingKeyMaterialException
     */
    public function validate(array $queryParams): ?AuthorizationRequestInterface;
}
