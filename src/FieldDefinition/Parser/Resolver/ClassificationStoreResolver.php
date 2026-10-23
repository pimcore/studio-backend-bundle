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

namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\ContainerTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use function count;
use function ctype_digit;
use function end;
use function method_exists;
use function sprintf;

/**
 * Resolves a classification store key in the dot notation "<storeFieldName>.<groupId>.<keyId>"
 * or, with the localization the key is edited in, "<storeFieldName>.<groupId>.<language>.<keyId>".
 *
 * @internal
 */
final class ClassificationStoreResolver extends AbstractResolver
{
    public function __construct(
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private readonly ServiceResolverInterface $classificationStoreServiceResolver,
    ) {
    }

    public function getResolverName(): string
    {
        return 'classificationstore';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        $count = count($dotNotationParts);
        if (($count !== 3 && $count !== 4)
            || !ctype_digit((string) $dotNotationParts[1])
            || !ctype_digit((string) $dotNotationParts[$count - 1])
        ) {
            return false;
        }

        try {
            $fd = $this->getFieldDefinition($dotNotationParts[0]);
        } catch (ParseException) {
            return false;
        }

        return $fd instanceof Classificationstore;
    }

    /**
     * @throws ParseException
     */
    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        // The key definition does not depend on the localization, so an optional language part is skipped
        $storeFieldName = $dotNotationParts[0];
        $groupId = $dotNotationParts[1];
        $keyId = end($dotNotationParts);

        if (!$this->getFieldDefinition($storeFieldName) instanceof Classificationstore) {
            throw new ParseException(sprintf('Field "%s" is not a classification store', $storeFieldName));
        }

        try {
            $keyGroupRelation = $this->keyGroupRelationRepository->getByKeyGroupId((int) $keyId, (int) $groupId);
        } catch (NotFoundException) {
            throw new ParseException(
                sprintf('Key "%s" does not exist in classification store group "%s"', $keyId, $groupId)
            );
        }

        $fd = $this->classificationStoreServiceResolver->getFieldDefinitionFromKeyConfig($keyGroupRelation);

        if ($fd === null) {
            throw new ParseException(sprintf('Classification store key "%s" has no field definition', $keyId));
        }

        if (method_exists($fd, '__wakeup')) {
            $fd->__wakeup();
        }

        return $this->wrapFieldDefinition(
            fieldDefinition: $fd,
            containerType: ContainerTypes::CLASSIFICATION_STORE->value,
            fieldname: $storeFieldName,
            // Same owner context as the core layout enrichment of classification store keys
            optionsProviderContext: [
                'ownerType' => ContainerTypes::CLASSIFICATION_STORE->value,
                'ownerName' => $storeFieldName,
                'keyId' => $keyGroupRelation->getKeyId(),
                'groupId' => $keyGroupRelation->getGroupId(),
                'keyDefinition' => $fd,
            ],
        );
    }
}
