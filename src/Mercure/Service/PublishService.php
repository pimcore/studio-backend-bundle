<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class PublishService implements PublishServiceInterface
{
    public function __construct(
        private HubInterface $serverHub,
        private LoggerInterface $logger,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param string|array<string> $topics
     *
     * @throws JsonEncodingException
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

        $jsonData = $this->serializer->serialize($data, 'json');

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

        $this->serverHub->publish(new Update($topics, $jsonData, $private, $id, $type, $retry));
    }
}
