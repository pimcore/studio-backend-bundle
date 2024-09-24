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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Link;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tool\Serialize;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class
LinkAdapter implements DataAdapterInterface
{
    /**
     * @throws Exception
     */
    public function getDataFromResource(Concrete $element, Data $fieldDefinition, string $key, array $data): mixed
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $data = clone $data[$key];
        $data->_setOwner(null);
        $data->_setOwnerFieldname('');
        $data->_setOwnerLanguage(null);

        if ($data->getLinktype() === 'internal' && !$data->getPath()) {
            $data->setLinktype(null);
            $data->setInternalType(null);
            if ($data->isEmpty()) {
                return null;
            }
        }

        $params['resetInvalidFields'] = true;
        $fieldDefinition->checkValidity($data, true, $params);

        return Serialize::serialize($data);
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return $fieldDefinitionClass === Link::class;
    }
}
