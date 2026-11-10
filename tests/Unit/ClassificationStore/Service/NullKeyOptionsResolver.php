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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUnits;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\OptionsUnavailableReason;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * A resolver that publishes nothing, for the tests whose subject is another member of the payload.
 *
 * @internal
 */
final class NullKeyOptionsResolver implements KeyOptionsResolverInterface
{
    public function getOptions(KeyConfig $keyConfig): ?array
    {
        return null;
    }

    public function getOptionsUnavailableReason(KeyConfig $keyConfig): ?OptionsUnavailableReason
    {
        return null;
    }

    public function getUnits(KeyConfig $keyConfig): ?KeyUnits
    {
        return null;
    }
}
