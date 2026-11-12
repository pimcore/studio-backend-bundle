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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Util;

use function in_array;
use function parse_url;
use function preg_match;
use function str_contains;
use function strtolower;

/**
 * Decides whether a redirect URI may be associated with a client at all.
 *
 * Every way a client can bring its own redirect URIs applies this: dynamic
 * registration (RFC 7591) and Client ID Metadata Documents. Both hand us URIs
 * chosen by whoever controls the client, and the authorization code is later
 * delivered to one of them, so a cleartext URI on a routable host would hand
 * the code to the network. https is required, with the RFC 8252 exception for
 * http on a loopback host so native apps can keep using a local listener.
 *
 * This is the registration-time gate. Matching a request's redirect_uri against
 * the ones a client registered is a separate concern, handled by
 * {@see \Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator}.
 *
 * @internal
 */
final class RedirectUriPolicy
{
    /**
     * Hosts that may be reached over plain http. Written unbracketed: getHost()
     * below strips the brackets an IPv6 literal carries in a URI.
     */
    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1'];

    public static function isAcceptable(string $uri): bool
    {
        // PHP parses a URI by RFC 3986; the browser that will follow it parses by
        // the WHATWG URL rules. Two constructs make them disagree about the host,
        // so they are refused before parsing rather than reconciled. A backslash
        // is ordinary userinfo text to PHP but an authority terminator to WHATWG:
        // "http://attacker.example\@localhost/cb" is host "localhost" to
        // parse_url() and host "attacker.example" to a browser, which would send
        // the authorization code in cleartext to a routable host. The ASCII
        // control characters WHATWG strips before parsing are the same class of
        // problem. (League's RFC 3986 parser agrees with PHP here, so parsing
        // more strictly does not help; only refusing the input does.)
        //
        // preg_match() returning false on malformed input also lands here.
        if (preg_match('/[\x00-\x20\x7f\\\\]/u', $uri) !== 0) {
            return false;
        }

        $parts = parse_url($uri);

        // An absolute URI is required, and a fragment is never permitted on a
        // redirect URI: the authorization response appends its own.
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || str_contains($uri, '#')) {
            return false;
        }

        // Userinfo is what makes a host ambiguous to both parsers and to the
        // person reading the consent screen, and a redirect target never needs it.
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme === 'https') {
            return true;
        }

        return $scheme === 'http' && in_array(self::getHost($parts['host']), self::LOOPBACK_HOSTS, true);
    }

    /**
     * parse_url() reports an IPv6 literal with its delimiting brackets, so
     * "http://[::1]:8080/cb" yields "[::1]". Strip that one pair to compare
     * against a plain address. Exactly one pair: parse_url() also accepts
     * malformed hosts such as "[[::1]]" and "[::1]]", and trimming every
     * bracket would let those through as loopback.
     */
    private static function getHost(string $host): string
    {
        if (preg_match('/^\[(.+)\]$/u', $host, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return strtolower($host);
    }
}
