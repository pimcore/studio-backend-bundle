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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri;

use League\OAuth2\Server\RedirectUriValidators\RedirectUriValidatorInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use function in_array;
use function is_string;

/**
 * Redirect-URI validator that applies the RFC 8252 §7.3 loopback exception:
 * for a loopback redirect the port is ignored when matching. League's built-in
 * validator only recognises the IP literals 127.0.0.1 and [::1]. RFC 8252 marks
 * the `localhost` host as NOT RECOMMENDED, but some native clients use it (e.g.
 * Claude Code, https://github.com/anthropics/claude-code/issues/42765); when
 * $allowLocalhost is true it is treated as loopback as well. All other URIs
 * still require an exact match.
 *
 * @internal
 */
final class LoopbackRedirectUriValidator implements RedirectUriValidatorInterface
{
    private const array IP_LOOPBACK_HOSTS = ['127.0.0.1', '[::1]'];

    /**
     * @var string[]
     */
    private readonly array $allowedRedirectUris;

    /**
     * @var list<string>
     */
    private readonly array $loopbackHosts;

    /**
     * @param string[]|string $allowedRedirectUris
     */
    public function __construct(array|string $allowedRedirectUris, bool $allowLocalhost = true)
    {
        $this->allowedRedirectUris = is_string($allowedRedirectUris) ? [$allowedRedirectUris] : $allowedRedirectUris;
        $this->loopbackHosts = $allowLocalhost
            ? [...self::IP_LOOPBACK_HOSTS, 'localhost']
            : self::IP_LOOPBACK_HOSTS;
    }

    public function validateRedirectUri(string $redirectUri): bool
    {
        if ($this->isLoopbackUri($redirectUri)) {
            return $this->matchUriExcludingPort($redirectUri);
        }

        return $this->matchExactUri($redirectUri);
    }

    private function isLoopbackUri(string $redirectUri): bool
    {
        try {
            $uri = Uri::new($redirectUri);
        } catch (SyntaxError) {
            return false;
        }

        return $uri->getScheme() === 'http' && in_array($uri->getHost(), $this->loopbackHosts, true);
    }

    private function matchExactUri(string $redirectUri): bool
    {
        return in_array($redirectUri, $this->allowedRedirectUris, true);
    }

    private function matchUriExcludingPort(string $redirectUri): bool
    {
        $parsed = $this->parseUrlAndRemovePort($redirectUri);

        foreach ($this->allowedRedirectUris as $allowedRedirectUri) {
            if ($parsed === $this->parseUrlAndRemovePort($allowedRedirectUri)) {
                return true;
            }
        }

        return false;
    }

    private function parseUrlAndRemovePort(string $url): string
    {
        return (string) Uri::new($url)->withPort(null);
    }
}
