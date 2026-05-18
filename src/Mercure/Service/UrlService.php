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

use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
final readonly class UrlService implements UrlServiceInterface
{
    public function __construct(
        private ?string $serverSideUrl,
        private ?string $clientSideUrl,
        private RequestStack $requestStack,
    ) {
    }

    public function getServerSideUrl(): string
    {
        if (empty($this->serverSideUrl)) {
            throw new LogicException('Mercure server URL is not configured.');
        }

        return $this->serverSideUrl;
    }

    public function getClientSideUrl(): string
    {
        if (empty($this->clientSideUrl)) {
            return $this->getDefaultClientUrl();
        }

        return str_replace(Mercure::HOST_PLACEHOLDER->value, $this->getHostUrl(), $this->clientSideUrl);
    }

    private function getDefaultClientUrl(): string
    {
        return $this->getHostUrl() . '/hub';
    }

    /**
     * @throws LogicException
     */
    private function getHostUrl(): string
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            throw new LogicException('Mercure fallback URL resolution requires an active HTTP request.');
        }

        return $request->getSchemeAndHttpHost();
    }
}
