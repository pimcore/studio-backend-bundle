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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * A stored key assignment together with the identifiers of the group and the key it joins. The
 * relation itself carries only the two row ids, which name nothing a file could be matched by.
 *
 * @internal
 */
final readonly class ScopedKeyAssignment
{
    public function __construct(
        private KeyGroupRelation $relation,
        private string $groupIdentifier,
        private string $keyIdentifier,
    ) {
    }

    public function getRelation(): KeyGroupRelation
    {
        return $this->relation;
    }

    public function getGroupIdentifier(): string
    {
        return $this->groupIdentifier;
    }

    public function getKeyIdentifier(): string
    {
        return $this->keyIdentifier;
    }
}
