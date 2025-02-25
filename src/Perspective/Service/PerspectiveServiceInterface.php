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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\AddPerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;

/**
 * @internal
 */
interface PerspectiveServiceInterface
{
    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function addConfig(AddPerspectiveConfig $config): string;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function updateConfig(string $perspectiveId, SavePerspectiveConfig $config): void;

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function getConfigData(string $perspectiveId): PerspectiveConfigDetail;

    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function listConfigurations(): array;

    /**
     * @throws NotWriteableException
     */
    public function deleteConfig(string $perspectiveId): void;
}
