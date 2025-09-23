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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
trait GetClassificationStoreFilterValueTrait
{
    public function getClassificationStoreFilterValue(array $rawFilterValue): ClassificationStoreFilterValue
    {

        if (!isset($rawFilterValue['groupId'])) {
            throw new InvalidArgumentException('Classificationstore filter need a groupId');
        }

        if (!isset($rawFilterValue['keyId'])) {
            throw new InvalidArgumentException('Classificationstore filter need a keyId');
        }

        if (!isset($rawFilterValue['value'])) {
            throw new InvalidArgumentException('Classificationstore filter need a value');
        }

        return new ClassificationStoreFilterValue(
            $rawFilterValue['groupId'],
            $rawFilterValue['keyId'],
            $rawFilterValue['value']
        );

    }
}
