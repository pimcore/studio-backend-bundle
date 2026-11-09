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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * Needs a `$columnReferenceService` of ColumnReferenceServiceInterface on the using class.
 *
 * @internal
 */
trait GetClassificationStoreFilterValueTrait
{
    /**
     * The identifiers of the filter value, when it carries both, are the reference and the integer pair is not
     * consulted. They are resolved in the store of the classification store field `$fieldName` of the filtered class.
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException when the identifiers do not resolve in this installation
     */
    public function getClassificationStoreFilterValue(
        array $rawFilterValue,
        mixed $parameters = null,
        string $fieldName = ''
    ): ClassificationStoreFilterValue {
        $classId = $parameters instanceof ClassIdParametersInterface ? $parameters->getClassId() : null;
        if ($classId !== null) {
            try {
                $reference = $this->columnReferenceService->resolve($classId, $fieldName, $rawFilterValue);
            } catch (InvalidArgumentException $exception) {
                throw new NotFoundException(
                    'Classificationstore reference',
                    $rawFilterValue['groupIdentifier'] . '/' . $rawFilterValue['keyIdentifier'],
                    'identifiers',
                    $exception
                );
            }

            if ($reference !== null) {
                $rawFilterValue['groupId'] = $reference->getGroupId();
                $rawFilterValue['keyId'] = $reference->getKeyId();
            }
        }

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
