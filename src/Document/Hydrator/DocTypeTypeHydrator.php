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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeType;

/**
 * @internal
 */
final class DocTypeTypeHydrator implements DocTypeTypeHydratorInterface
{
    public function hydrate(string $name, array $typeData): DocTypeType
    {
        return new DocTypeType(
            name: $name,
            validTable: $typeData['valid_table'] ?? '',
            childrenSupported: $typeData['children_supported'] ?? false,
            directRoute: $typeData['direct_route'] ?? false,
            predefinedDocumentTypes: $typeData['predefined_document_types'] ?? false,
            translatable: $typeData['translatable'] ?? false,
            translatableInheritance: $typeData['translatable_inheritance'] ?? false,
            onlyPrintableChildren: $typeData['only_printable_children'] ?? false,
        );
    }
}
