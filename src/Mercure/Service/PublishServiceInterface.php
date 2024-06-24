<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;

interface PublishServiceInterface
{
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
    ): void;
}
