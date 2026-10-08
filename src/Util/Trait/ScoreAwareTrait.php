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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use OpenApi\Attributes\Property;

/**
 * Public API: default implementation of ScoreAwareInterface for third-party response DTOs.
 */
trait ScoreAwareTrait
{
    #[Property(
        description: 'Search relevance score of the hit in the search engine\'s own score space; '
            . 'null unless the request ran a scored search',
        type: 'number',
        format: 'float',
        example: 0.63,
        nullable: true
    )]
    private ?float $score = null;

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }
}
