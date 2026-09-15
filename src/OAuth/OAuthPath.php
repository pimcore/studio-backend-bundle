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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth;

/**
 * The URL space the embedded authorization server occupies.
 *
 * Companion to {@see \Pimcore\Bundle\StudioBackendBundle\Mcp\McpPath}, and for the same
 * reason: the endpoint guard, the CORS subscriber, the rate limiter and the metadata
 * documents each used to spell these paths out for themselves, and two of the copies had
 * already drifted apart. The routing file still declares the literals, because YAML cannot
 * read a PHP constant, but everything that has to decide "is this an OAuth request" now
 * decides it from one list.
 *
 * @internal
 */
final class OAuthPath
{
    /**
     * The dedicated OAuth base, no trailing slash. Outside the Studio API url_prefix on
     * purpose: these endpoints are public and cookie-less, the Studio API is neither.
     */
    public const string BASE = '/pimcore-oauth';

    /**
     * The base as a path prefix, for matching requests that live under it.
     */
    public const string PREFIX = self::BASE . '/';

    public const string AUTHORIZE = self::PREFIX . 'authorize';

    public const string TOKEN = self::PREFIX . 'token';

    public const string REGISTER = self::PREFIX . 'register';

    /**
     * Shared prefix of the two RFC metadata documents, which live at the host root rather
     * than under {@see self::BASE} because their location is fixed by RFC 8414 and RFC 9728.
     */
    public const string METADATA_PREFIX = '/.well-known/oauth-';

    public const string PROTECTED_RESOURCE_METADATA = self::METADATA_PREFIX . 'protected-resource';

    public const string AUTHORIZATION_SERVER_METADATA = self::METADATA_PREFIX . 'authorization-server';

    /**
     * Every root-level path this server answers on. Fixed, unlike the Studio API prefix,
     * so these are matched literally.
     *
     * The consent API is not in here: it is a Studio API endpoint that happens to serve
     * OAuth, it sits below the configurable Studio prefix, and it is cookie-authenticated.
     * Callers that need it join {@see self::API_SUFFIX} to that prefix themselves.
     *
     * @var list<string>
     */
    public const array ROOT_PREFIXES = [
        self::METADATA_PREFIX,
        self::PREFIX,
    ];

    /**
     * Consent API path below the (configurable) Studio API prefix.
     */
    public const string API_SUFFIX = '/oauth/';
}
