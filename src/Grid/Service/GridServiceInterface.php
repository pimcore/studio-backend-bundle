<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
interface GridServiceInterface
{
    public function getAssetGridConfiguration(): Configuration;

    public function getDocumentGridColumns(): Configuration;

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): Configuration;

    public function getGridDataForElement(
        Configuration $configuration,
        ElementInterface $element,
         string $elementType
    ): array;

    public function getConfigurationFromArray(array $config): Configuration;
}
