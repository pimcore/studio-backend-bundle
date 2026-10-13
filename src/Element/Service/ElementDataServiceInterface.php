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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Model\Element\ElementInterface;

interface ElementDataServiceInterface
{
    public function getRelatedElementData(ElementInterface $element): RelatedElementData;

    /**
     * Builds the related element data from a relation summary (see LightweightRelationLoadingInterface) without
     * loading the related element.
     *
     * @param array{
     *     id: int,
     *     parentId: int|null,
     *     type: string,
     *     subtype: string,
     *     className: string|null,
     *     fullpath: string,
     *     filename: string,
     *     published: bool
     * } $summary
     */
    public function getRelatedElementDataFromSummary(array $summary): RelatedElementData;
}
