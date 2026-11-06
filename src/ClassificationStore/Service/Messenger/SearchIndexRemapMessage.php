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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger;

/**
 * One search index remap, asked for because a classification store key changed its search index
 * flag. Scalars only — it rides the pimcore_core transport into a separate worker, and the class
 * definition is loaded there rather than carried along, the way the generic data index bundle has to
 * reload its own ({@see \Pimcore\Bundle\GenericDataIndexBundle\MessageHandler\UpdateClassMappingHandler}
 * reloads a serialized one because nested field definitions do not survive the trip).
 *
 * @internal
 */
final readonly class SearchIndexRemapMessage
{
    public function __construct(
        private string $classDefinitionId,
    ) {
    }

    public function getClassDefinitionId(): string
    {
        return $this->classDefinitionId;
    }
}
