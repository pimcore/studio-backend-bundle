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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface as ObjectBrickDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use function count;
use function sprintf;

/**
 * @internal
 */
final class ObjectBrickResolver extends AbstractResolver
{
    public function __construct(
        private readonly ObjectBrickDefinitionResolverInterface $objectBrickDefinitionResolver,
        private readonly LocalizedFieldServiceInterface $localizedFieldService,
    ) {
    }

    public function getResolverName(): string
    {
        return 'objectbrick';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if (!count($dotNotationParts) >= 3) {
            return  false;
        }

        try {
            $fd = $this->getFieldDefinition($dotNotationParts[0]);
        } catch (Exception) {
            return false;
        }

        if ($fd instanceof Data\Objectbricks) {
            return true;
        }

        return false;
    }

    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {

        $objectBrickFd = $this->getFieldDefinition($dotNotationParts[0]);

        if (!$objectBrickFd instanceof Data\Objectbricks) {
            throw new ParseException(sprintf('Field "%s" is not of type Object bricks', $dotNotationParts[0]));
        }

        $isAllowed = array_find($objectBrickFd->getAllowedTypes(), function (string $type) use ($dotNotationParts) {
            return $type === $dotNotationParts[1];
        });

        if (!$isAllowed) {
            throw new ParseException(sprintf('Object brick "%s" is not allowed/found in field "%s"', $dotNotationParts[1], $dotNotationParts[0]));
        }

        $objectBrickDefinition = $this->objectBrickDefinitionResolver->getByKey($dotNotationParts[1]);

        $fd = $objectBrickDefinition->getFieldDefinition($dotNotationParts[2]);

        if ($fd === null) {
            throw new ParseException(sprintf('Field Definition "%s" does not exist in object brick "%s"', $dotNotationParts[2], $dotNotationParts[1]));
        }

        $localized = false;
        if ($fd instanceof Data\Localizedfields && $dotNotationParts[3]) {
            $localized = true;
            $fd = $this->localizedFieldService->getFieldDefinition($fd, $dotNotationParts[3]);
        }

        $fd = $this->checkForSubBlockContainer($fd, $dotNotationParts, $objectBrickDefinition);

        return $this->wrapFieldDefinition(
            fieldDefinition: $fd,
            containerType: 'objectbrick',
            fieldname: $dotNotationParts[3],
            subContainerType: $localized ? 'localizedfield' : null,
            subContainerKey: $localized ? $dotNotationParts[3] : null,
        );
    }
}
