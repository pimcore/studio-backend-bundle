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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Request;

use Pimcore\Bundle\StudioBackendBundle\Request\CollectionParameters;

/**
 * @internal
 */
final readonly class VersionParameters extends CollectionParameters
{
    public function __construct(
        int $page,
        int $pageSize,
        private int $elementId,
        private string $elementType
    ) {
        parent::__construct($page, $pageSize);
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }
}