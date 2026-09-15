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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;

/**
 * Dispatched before the consent screen's payload is returned, so an integration can enrich
 * how a client is presented to the user.
 *
 * A listener must not change what the screen says is being granted. The scopes on this
 * payload are the ones the authorization request actually carries, already narrowed to what
 * the named resource supports, and the user approves on the strength of seeing them. A
 * listener that rewrites them makes the screen disagree with the token that follows, which
 * is the one thing a consent screen may never do.
 */
final class AuthorizationConsentEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.oauth.authorization_consent';

    public function __construct(
        private readonly AuthorizationConsent $consent
    ) {
        parent::__construct($consent);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getConsent(): AuthorizationConsent
    {
        return $this->consent;
    }
}
