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

namespace Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter;

use InvalidArgumentException;

/**
 * @internal
 */
final readonly class CreateClassDefinitionParameters
{
    public function __construct(
        private string $name,
        private string $uid
    ) {
        if (trim($name) === '' || trim($uid) === '') {
            throw new InvalidArgumentException('Class name and UID cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
