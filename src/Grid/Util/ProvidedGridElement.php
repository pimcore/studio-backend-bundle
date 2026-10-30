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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Model\Element\ElementInterface;

/**
 * A grid row's element supplied by the caller instead of the search index and the database:
 * the entry stands in for the index item, the element for the one loaded by id.
 *
 * @internal
 */
final readonly class ProvidedGridElement
{
    public function __construct(
        private StudioElementInterface $entry,
        private ElementInterface $element,
    ) {
    }

    public function getEntry(): StudioElementInterface
    {
        return $this->entry;
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }
}
