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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use function count;
use function sprintf;

/**
 * @internal
 */
final class FieldCollectionResolver extends AbstractResolver
{
    public function __construct(
        private readonly DefinitionResolverInterface $fieldCollectionDefinitionResolver,
        private readonly LocalizedFieldServiceInterface $localizedFieldService,
    ) {
    }

    public function getResolverName(): string
    {
        return 'field_collection';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if (!count($dotNotationParts) >= 3) {
            return false;
        }

        try {
            $fd = $this->getFieldDefinition($dotNotationParts[0]);
        } catch (ParseException) {
            return false;
        }

        if ($fd instanceof Fieldcollections) {
            return true;
        }

        return false;
    }

    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        $fieldCollectionFd = $this->getFieldDefinition($dotNotationParts[0]);

        if (!$fieldCollectionFd instanceof Fieldcollections) {
            throw new ParseException(sprintf('Field definition "%s" is not a field collection', $dotNotationParts[0]));
        }

        $fieldCollectionDefinition = $this->fieldCollectionDefinitionResolver->getByKey($dotNotationParts[1]);

        if (!$fieldCollectionDefinition) {
            throw new ParseException(sprintf('Field collection "%s" not found', $dotNotationParts[1]));
        }

        $fd = $fieldCollectionDefinition->getFieldDefinition($dotNotationParts[2]);

        if ($fd === null) {
            throw new ParseException(
                sprintf(
                    'Field collection "%s" does not have a field "%s"',
                    $dotNotationParts[1],
                    $dotNotationParts[2]
                )
            );
        }

        $localized = false;
        if ($fd instanceof Data\Localizedfields && $dotNotationParts[3]) {
            $localized = true;
            $fd = $this->localizedFieldService->getFieldDefinition($fd, $dotNotationParts[3]);
        }

        $fd = $this->checkForSubBlockContainer($fd, $dotNotationParts, $fieldCollectionDefinition);

        return $this->wrapFieldDefinition(
            fieldDefinition: $fd,
            containerType: 'fieldcollection',
            fieldname: $dotNotationParts[2],
            subContainerType: $localized ? 'localizedfield' : null,
            subContainerKey: $localized ? $dotNotationParts[3] : null,
        );
    }
}
