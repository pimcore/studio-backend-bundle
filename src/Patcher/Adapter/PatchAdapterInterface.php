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
