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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Hydrator;

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface AuthorizationConsentHydratorInterface
{
    public function hydrate(
        string $authorizationId,
        AuthorizationRequestInterface $authorizationRequest,
        ?UserInterface $user,
    ): AuthorizationConsent;
}
