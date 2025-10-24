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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;

/**
 * @internal
 */
interface PhpCodeTransformerLoaderInterface
{
    public const PHPCODE_TRANSFORMER_TAG = 'pimcore.studio_backend.phpcode_transformer';

    /**
     * @return array<PhpCodeTransformerInterface>
     */
    public function getTransformers(): array;

    /**
     * @throws InvalidArgumentException If no matching transformer is found
     */
    public function resolve(string $key): PhpCodeTransformerInterface;
}
