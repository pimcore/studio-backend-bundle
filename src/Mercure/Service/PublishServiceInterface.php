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

interface PublishServiceInterface
{
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
    ): void;

    public function getJsonData(mixed $data): string;
}
