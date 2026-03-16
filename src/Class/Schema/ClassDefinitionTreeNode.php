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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    schema: 'ClassDefinitionTreeNode',
    title: 'Class Definition Tree Node Item',
    required: [
        'enableGridLocking',
        'hasBrickField',
    ],
    type: 'object'
)]
final class ClassDefinitionTreeNode extends ClassDefinitionList
{
    public function __construct(
        string $id,
        string $name,
        string $title,
        ElementIcon $icon,
        ?string $group = null,
        #[Property(description: 'Enable grid locking', type: 'bool', example: false)]
        private readonly bool $enableGridLocking = false,
        #[Property(description: 'Has brick field', type: 'bool', example: false)]
        private readonly bool $hasBrickField = false,
    ) {
        parent::__construct($id, $name, $title, $icon, $group);
    }

    public function isEnableGridLocking(): bool
    {
        return $this->enableGridLocking;
    }

    public function isHasBrickField(): bool
    {
        return $this->hasBrickField;
    }
}
