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
        private ?string $resourceIdentifier = null,
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

    public function getResourceIdentifier(): string
    {
        if (empty($this->resourceIdentifier)) {
            return $this->getServerSideUrl();
        }

        return $this->resourceIdentifier;
    }

    public function getSubscriberAudience(): array
    {
        if (!empty($this->resourceIdentifier)) {
            return [$this->resourceIdentifier];
        }

        $audience = [$this->getServerSideUrl()];
        $client = parse_url($this->getClientSideUrl());
        if (isset($client[Mercure::URL_HOST->value])) {
            $authority = $client[Mercure::URL_HOST->value] . (isset($client['port']) ? ':' . $client['port'] : '');
            // A proxy forwarding the public host makes the hub identify itself by that host, often over plain HTTP
            // because TLS ends at the proxy
            $schemes = array_unique([$client[Mercure::URL_SCHEME->value] ?? 'https', 'http']);
            foreach ($schemes as $scheme) {
                $audience[] = $scheme . '://' . $authority . Mercure::HUB_PATH->value;
            }
        }

        return array_values(array_unique($audience));
    }

    private function getDefaultClientUrl(): string
    {
        return $this->getHostUrl() . Mercure::HUB_PATH->value;
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
