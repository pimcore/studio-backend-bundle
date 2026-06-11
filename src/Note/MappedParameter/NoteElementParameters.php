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

namespace Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;

/**
 * @internal
 */
final readonly class NoteElementParameters
{
    use ValidateElementTypeTrait;

    public function __construct(
        private ?string $type = null,
        private ?int $id = null,

    ) {
        $this->validate($this->type);
    }

    public function getType(): ?string
    {
        return $this->mapElementType($this->type);
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
