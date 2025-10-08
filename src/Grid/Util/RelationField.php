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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

/**
 * @internal
 */
final readonly class RelationField extends SimpleField
{
    /**
     * @param SimpleField[] $fields
     */
    public function __construct(string $name, string $key, private array $classIds, private array $fields)
    {
        parent::__construct($name, $key);
    }

    /**
     * @return SimpleField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getClassIds(): array
    {
        return $this->classIds;
    }
}
