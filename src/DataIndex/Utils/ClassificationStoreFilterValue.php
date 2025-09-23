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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils;

use IntlDateFormatter;



/**
 * @internal
 */
final readonly class ClassificationStoreFilterValue
{
    public function __construct(
        private readonly int $groupId,
        private readonly int $keyId,
        private readonly string|array $value
    ) {

    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getValue(): string|array
    {
        return $this->value;
    }
}
