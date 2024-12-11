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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class SelectOptionsParameter
{
    public function __construct(
        #[NotBlank(message: 'The object id must not be empty.')]
        private int $objectId,
        #[NotBlank(message: 'The field name must not be empty.')]
        private string $fieldName,
        private array $context,
        private array $changedData = [],

    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getChangedData(): array
    {
        return $this->changedData;
    }

    public function hasChangedData(): bool
    {
        return !empty($this->changedData);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
