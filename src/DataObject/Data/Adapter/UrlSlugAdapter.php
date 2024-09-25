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
use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug as UrlSlugDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\Site;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final class UrlSlugAdapter extends AbstractAdapter
{
    /**
     * @throws Exception
     */
    public function getDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): array
    {
        if (!array_key_exists($key, $data)) {
            return [];
        }

        $urlData = $data[$key];
        if (!is_array($urlData)) {
            return [];
        }
        $result = [];
        foreach ($urlData as $slug) {
            if ($slug instanceof UrlSlug) {
                $siteId = $slug->getSiteId();
                $resultItem = [
                    'slug' => $slug->getSlug(),
                    'siteId' => $siteId,
                    'domain' => $this->getDomain($siteId),
                ];

                $result[$siteId] = $resultItem;
            }
        }

        return $result;
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return $fieldDefinitionClass === UrlSlugDefinition::class;
    }

    /**
     * @throws Exception
     */
    private function getDomain(?int $siteId): ?string
    {
        if ($siteId === null) {
            return null;
        }

        return Site::getById($siteId)?->getMainDomain();
    }
}
