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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

/**
 * A fresh configuration identifier, in the form core assigns one in
 * {@see \Pimcore\Model\DataObject\Classificationstore\IdentifierBackfill}. Behind an interface
 * because an import that regenerates identifiers has to be assertable without reading the rows it
 * wrote.
 *
 * @internal
 */
interface IdentifierGeneratorInterface
{
    public function generate(): string;
}
