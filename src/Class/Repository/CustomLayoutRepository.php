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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing;

/**
 * @internal
 */
readonly class CustomLayoutRepository implements CustomLayoutRepositoryInterface
{
    public function __construct(
    ) {
    }

    public function getCustomLayouts(string $dataObjectClassId): array
    {
        $customLayoutListing = new Listing();
        $customLayoutListing->setFilter(
            fn (CustomLayout $layout) =>
                $layout->getClassId() === $dataObjectClassId &&
                !str_contains($layout->getId(), '.brick.')
        );

        return $customLayoutListing->load();
    }
}
