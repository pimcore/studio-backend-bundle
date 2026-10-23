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

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use function is_string;
use function sprintf;

final class PublishService implements PublishServiceInterface
{
    private ?Hub $publisher = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly UrlServiceInterface $urlService,
    ) {
    }

    /**
     * @param string|array<string> $topics
     */
    public function publish(
        string|array $topics,
        mixed $data,
        bool $private = true,
        ?string $id = null,
        ?string $type = null,
        ?int $retry = null
    ): void {
        if (is_string($topics)) {
            $topics = [$topics];
        }

        $jsonData = $this->getJsonData($data);

        $this->logger->debug(
            sprintf(
                'Publishing to %s topic(s): %s with data: %s (id: %s, type: %s, retry: %d)',
                $private ? 'private' : 'public',
                implode(',', $topics),
                $jsonData,
                $id ?? 'null',
                $type ?? 'null',
                $retry ?? -1
            )
        );

        $this->getServerHub()->publish(new Update($topics, $jsonData, $private, $id, $type, $retry));
    }

    public function getJsonData(mixed $data): string
    {
        return $this->serializer->serialize($data, 'json');
    }

    private function getServerHub(): Hub
    {
        if ($this->publisher === null) {
            $this->publisher = new Hub(
                $this->urlService->getServerSideUrl(),
                new StaticTokenProvider($this->tokenProvider->getJwt()),
            );
        }

        return $this->publisher;
    }
}
