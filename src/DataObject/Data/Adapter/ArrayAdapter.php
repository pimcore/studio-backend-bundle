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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Languagemultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final class ArrayAdapter extends AbstractAdapter
{
    public function getDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): ?array
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        if (!is_array($data[$key])) {
            return null;
        }

        return $data[$key];
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return  in_array(
            $fieldDefinitionClass,
            [
                Countrymultiselect::class,
                Languagemultiselect::class,
                Multiselect::class,
            ]
        );
    }
}
