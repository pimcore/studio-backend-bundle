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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface KeyGroupLayoutServiceInterface
{
    /**
     * @throws Exception
     */
    public function getLayoutDefinition(
        KeyGroupRelation $keyGroupRelation,
        string $fieldName,
        ?Concrete $object = null,
    ): EncryptedField|Data;

    public function getKeyLayout(
        LayoutParameter $layoutParameter,
        int $keyId,
    ): KeyLayout;
}
