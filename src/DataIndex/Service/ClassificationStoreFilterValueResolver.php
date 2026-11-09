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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;

/**
 * @internal
 */
final readonly class ClassificationStoreFilterValueResolver implements ClassificationStoreFilterValueResolverInterface
{
    public function __construct(
        private ColumnReferenceServiceInterface $columnReferenceService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        array $rawFilterValue,
        mixed $parameters = null,
        string $fieldName = ''
    ): ClassificationStoreFilterValue {
        $classId = $parameters instanceof ClassIdParametersInterface ? $parameters->getClassId() : null;
        if ($classId !== null) {
            $reference = $this->resolveReference($classId, $fieldName, $rawFilterValue);

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

    /**
     * @throws NotFoundException when the identifiers do not resolve in this installation
     */
    private function resolveReference(
        string $classId,
        string $fieldName,
        array $rawFilterValue
    ): ?ClassificationStoreConfig {
        try {
            return $this->columnReferenceService->resolve($classId, $fieldName, $rawFilterValue);
        } catch (InvalidArgumentException $exception) {
            throw new NotFoundException(
                'Classificationstore reference',
                $rawFilterValue['groupIdentifier'] . '/' . $rawFilterValue['keyIdentifier'],
                'identifiers',
                $exception
            );
        }
    }
}
