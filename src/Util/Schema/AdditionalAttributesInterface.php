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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Schema;

/**
 * @internal
 */
interface AdditionalAttributesInterface
{
    public function hasAdditionalAttribute(string $key): bool;

    public function getAdditionalAttribute(string $key): mixed;

    public function addAdditionalAttribute(string $key, mixed $value): void;

    public function removeAdditionalAttribute(string $key): void;
}
