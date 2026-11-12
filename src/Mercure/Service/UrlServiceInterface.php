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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

interface UrlServiceInterface
{
    public function getServerSideUrl(): string;

    public function getClientSideUrl(): string;

    /**
     * The hub's resource identifier, i.e. the audience of the publisher's access tokens.
     */
    public function getResourceIdentifier(): string;

    /**
     * Every resource identifier the hub may derive for a browser, i.e. the audience of the subscribers' access tokens.
     *
     * @return string[]
     */
    public function getSubscriberAudience(): array;
}
