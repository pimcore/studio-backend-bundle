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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

interface PatchAdapterInterface
{
    public function patch(ElementInterface $element, array $data, UserInterface $user): void;

    public function getIndexKey(): string;

    /**
     * @return array<string>
     */
    public function supportedElementTypes(): array;
}
