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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Service;

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationRedirect;

/**
 * @internal
 */
interface AuthorizationConsentServiceInterface
{
    /**
     * The pending authorization behind an opaque id, as the consent screen needs it.
     *
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    public function getConsent(string $authorizationId): AuthorizationConsent;

    /**
     * Completes a pending authorization with the user's decision and returns where the
     * browser goes next. A denial is not an error: it becomes an `access_denied` redirect
     * back to the client, which is how the client learns the answer.
     *
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    public function completeConsent(string $authorizationId, bool $approved): AuthorizationRedirect;

    /**
     * The authorization parameters to stash for the consent screen.
     *
     * The scope is written back as league narrowed it for the named resource, not as the
     * client sent it. Left as sent, a configuration change between the redirect and the
     * approval would grant a wider set than the screen showed, binding the user to
     * something they never saw.
     *
     * @param array<string, mixed> $queryParams
     *
     * @return array<string, mixed>
     */
    public function pinConsentParameters(
        array $queryParams,
        AuthorizationRequestInterface $authorizationRequest,
    ): array;
}
