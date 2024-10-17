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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use function is_array;

/**
 * @internal
 */
final readonly class FieldContextData
{
    public function __construct(
        private AbstractData|array|Fieldcollection|null $contextObject = null,
        private ?string $language = null
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContextObject(): Fieldcollection|array|AbstractData|null
    {
        return $this->contextObject;
    }

    public function getFieldValueFromContextObject(string $fieldName): mixed
    {
        $contextObject = $this->getContextObject();

        if ($contextObject instanceof Fieldcollection || $contextObject instanceof AbstractData) {
            return $contextObject->get($fieldName);
        }

        if (is_array($contextObject)) {
            return $this->getFromBlockData($fieldName, $contextObject);
        }

        return null;
    }

    private function getFromBlockData(string $fieldName, array $blockData): mixed
    {
        foreach ($blockData as $value) {
            $fieldValue = $value[$fieldName] ?? null;
            if ($fieldValue instanceof BlockElement) {
                return $fieldValue->getData();
            }
        }

        return null;
    }
}
