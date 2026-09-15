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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Dto;

/**
 * Client metadata resolved from a Client ID Metadata Document (CIMD): the
 * client_id is an HTTPS URL the authorization server fetches to learn the
 * client's name and redirect URIs. Such clients are always public (PKCE, no
 * secret) since there is no registration step that could issue one.
 *
 * @internal
 */
final readonly class ClientMetadata
{
    /**
     * @param list<string> $redirectUris
     */
    public function __construct(
        public string $clientId,
        public string $name,
        public array $redirectUris,
    ) {
    }
}
