<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class PublishService implements PublishServiceInterface
{
    public function __construct(
        private HubInterface $serverHub,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string|array<string> $topics
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

        try {
            $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new JsonEncodingException('Failed to encode data to JSON: ' . $e->getMessage(), 0, $e);
        }

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
