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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;
use function array_key_exists;

/**
 * @internal
 */
trait DefaultSetterValueTrait
{
    public function getDefaultDataForSetter(Data $fieldDefinition, string $key, array $data): mixed
    {
        if (!array_key_exists($key, $data) || !$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        return $fieldDefinition->denormalize($data[$key]);
    }
}
