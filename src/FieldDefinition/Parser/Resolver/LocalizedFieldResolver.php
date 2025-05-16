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

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
final class LocalizedFieldResolver extends AbstractResolver
{
    public function __construct(
        private readonly LocalizedFieldServiceInterface $localizedFieldService,
    )
    {
    }

    public function getResolverName(): string
    {
        return 'localizedfields';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if ($dotNotationParts[0] === 'localizedfields' && count($dotNotationParts) >= 2) {
            return true;
        }

        return false;
    }

    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        $key = $dotNotationParts[1];
        if (!array_key_exists('localizedfields', $this->getFieldDefinitions())) {
            throw new ParseException("Class Definition has no localized fields");
        }

        $localizedFields = $this->getFieldDefinitions()['localizedfields'];

        if (!$localizedFields instanceof Data\Localizedfields) {
            throw new ParseException("Class Definition has to be of type Localizedfields");
        }

        return $this->wrapFieldDefinition(
            fieldDefinition: $this->localizedFieldService->getFieldDefinition($localizedFields, $key),
            containerType:  'localizedfield',
            fieldname: $key,
        );
    }
}