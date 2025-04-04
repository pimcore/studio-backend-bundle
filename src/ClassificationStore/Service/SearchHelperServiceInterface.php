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


namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing as CollectionConfigListing;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupConfigListing;

/**
 * @internal
 */
interface SearchHelperServiceInterface
{
    public function applySearchTermFilter(GroupConfigListing|CollectionConfigListing $list, string $searchTerm): void;

    public function getTranslatedSearchFilterTerms(string $searchTerm): array;
}