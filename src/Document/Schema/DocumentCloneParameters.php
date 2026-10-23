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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Document Clone Parameters',
    required: ['language', 'enableInheritance', 'recursive', 'updateReferences'],
    type: 'object'
)]
final readonly class DocumentCloneParameters
{
    public function __construct(
        #[Property(description: 'Language for the new translation', type: 'string', example: 'en')]
        private ?string $language = null,
        #[Property(description: 'Enable Inheritance', type: 'bool', example: false)]
        private bool $enableInheritance = false,
        #[Property(description: 'Recursive', type: 'bool', example: false)]
        private bool $recursive = false,
        #[Property(description: 'Update References', type: 'bool', example: false)]
        private bool $updateReferences = false,
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function isEnableInheritance(): bool
    {
        return $this->enableInheritance;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    public function isUpdateReferences(): bool
    {
        return $this->updateReferences;
    }
}
