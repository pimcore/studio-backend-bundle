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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface as FieldCollectionDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface as ObjectBrickDefinitionResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;

/**
 *  Copied from old admin-ui-classic-bundle
 *  https://github.com/pimcore/admin-ui-classic-bundle/blob/2013cc6e37f5b3dfffa9399921e0a12008b8bd8f/src/Controller/Admin/ElementController.php#L859
 *  Use with caution, this is a copy from the admin-ui-classic-bundle
 *
 * @internal
 */
final readonly class PathFormatterHelper implements PathFormatterHelperInterface
{
    public function __construct(
        private ObjectBrickDefinitionResolverInterface $objectBrickDefinitionResolver,
        private FieldCollectionDefinitionResolverInterface $fieldCollectionDefinitionResolver
    ) {
    }

    public function getPathFormatterFieldDefinition(Concrete $source, array $context): Data|bool|null
    {
        $ownerType = $context['containerType'];
        $fieldname = $context['fieldname'];
        $fd = null;

        if ($ownerType == 'object') {
            $subContainerType = isset($context['subContainerType']) ? $context['subContainerType'] : null;
            if ($subContainerType) {
                $subContainerKey = $context['subContainerKey'];
                $subContainer = $source->getClass()->getFieldDefinition($subContainerKey);
                if (method_exists($subContainer, 'getFieldDefinition')) {
                    $fd = $subContainer->getFieldDefinition($fieldname);
                }
            } else {
                $fd = $source->getClass()->getFieldDefinition($fieldname);
            }
        } elseif ($ownerType == 'localizedfield') {
            $localizedfields = $source->getClass()->getFieldDefinition('localizedfields');
            if ($localizedfields instanceof Localizedfields) {
                $fd = $localizedfields->getFieldDefinition($fieldname);
            }
        } elseif ($ownerType == 'objectbrick') {
            $fdBrick = $this->objectBrickDefinitionResolver->getByKey($context['containerKey']);
            $fd = $fdBrick->getFieldDefinition($fieldname);
        } elseif ($ownerType == 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $fdCollection = $this->fieldCollectionDefinitionResolver->getByKey($containerKey);
            if (($context['subContainerType'] ?? null) === 'localizedfield') {
                /** @var Localizedfields $fdLocalizedFields */
                $fdLocalizedFields = $fdCollection->getFieldDefinition('localizedfields');
                $fd = $fdLocalizedFields->getFieldDefinition($fieldname);
            } else {
                $fd = $fdCollection->getFieldDefinition($fieldname);
            }
        }

        return $fd;
    }
}
