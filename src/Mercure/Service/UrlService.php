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

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;

/**
 * @internal
 */
final readonly class UrlService implements UrlServiceInterface
{
    public function __construct(
        private ?string $serverSideUrl,
        private ?string $clientSideUrl,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    public function getServerSideUrl(): string
    {
        if (empty($this->serverSideUrl)) {
            return $this->getDefaultUrl();
        }

        return $this->serverSideUrl;
    }

    public function getClientSideUrl(): string
    {
        if (empty($this->clientSideUrl)) {
            return $this->getDefaultUrl();
        }

        return str_replace(Mercure::HOST_PLACEHOLDER->value, $this->toolResolver->getHostUrl(), $this->clientSideUrl);
    }

    private function getDefaultUrl(): string
    {
        return $this->toolResolver->getHostUrl() . '/hub';
    }
}
