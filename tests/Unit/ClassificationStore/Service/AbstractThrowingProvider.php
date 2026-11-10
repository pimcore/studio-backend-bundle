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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Throwable;

/**
 * A provider that cannot be asked: it answers that its options are static and then raises.
 *
 * @internal
 */
abstract class AbstractThrowingProvider implements SelectOptionsProviderInterface
{
    abstract protected function failure(): Throwable;

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        throw $this->failure();
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): string|array|null
    {
        return null;
    }
}
