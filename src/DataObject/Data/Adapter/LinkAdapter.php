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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Link as LinkDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Link;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final class LinkAdapter extends AbstractAdapter
{
    /**
     * @throws Exception
     */
    public function getDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): ?Link
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $link = new Link();
        $link->setValues($data[$key]);

        if ($link->isEmpty()) {
            return null;
        }

        return $link;
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return $fieldDefinitionClass === LinkDefinition::class;
    }
}
