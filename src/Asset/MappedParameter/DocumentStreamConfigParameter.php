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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

/**
 * @internal
 */
final readonly class DocumentStreamConfigParameter extends StreamCropParameter
{
    public function __construct(
        private ?int $page = null,
        bool $cropPercent = false,
        ?float $cropHeight = null,
        ?float $cropWidth = null,
        ?float $cropTop = null,
        ?float $cropLeft = null,
    ) {
        parent::__construct($cropPercent, $cropHeight, $cropWidth, $cropTop, $cropLeft);
    }

    public function getPage(): ?int
    {
        return $this->page;
    }
}
