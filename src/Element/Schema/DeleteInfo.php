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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'DeleteInfo',
    required: ['hasDependencies', 'canUseRecycleBin'],
    type: 'object'
)]
final class DeleteInfo implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'hasDependencies', type: 'boolean', example: true)]
        private readonly bool $hasDependencies,
        #[Property(description: 'canUseRecycleBin', type: 'boolean', example: true)]
        private readonly bool $canUseRecycleBin,
    ) {

    }

    public function getHasDependencies(): bool
    {
        return $this->hasDependencies;
    }

    public function getCanUseRecycleBin(): bool
    {
        return $this->canUseRecycleBin;
    }
}
