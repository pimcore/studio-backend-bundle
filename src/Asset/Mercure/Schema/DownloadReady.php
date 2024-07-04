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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'SSEDownloadReady',
    required: [
        'id',
        'path',
        'user',
    ],
    type: 'object'
)]
final readonly class DownloadReady
{
    public function __construct(
        #[Property(description: 'jobRunId', type: 'integer', example: 1)]
        private int $jobRunId,
        #[Property(description: 'path', type: 'string', example: '/path/to/assets.zip')]
        private string $path,
        #[Property(description: 'user', type: 'integer', example: 2)]
        private int $user
    ) {
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUser(): int
    {
        return $this->user;
    }
}
