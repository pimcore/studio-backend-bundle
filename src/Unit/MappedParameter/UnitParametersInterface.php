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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter;

/**
 * @internal
 */
interface UnitParametersInterface
{
    public function getAbbreviation(): ?string;

    public function getLongname(): ?string;

    public function getGroup(): ?string;

    public function getBaseunit(): ?string;

    public function getFactor(): ?float;

    public function getConversionOffset(): ?float;

    public function getConverter(): ?string;

    public function getReference(): ?string;
}
