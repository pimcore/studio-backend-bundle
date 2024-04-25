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

namespace Pimcore\Bundle\StudioBackendBundle\Dto;

readonly class Task
{
    public function __construct(private \Pimcore\Model\Schedule\Task $task)
    {
    }

    public function getId(): ?int
    {
        return $this->task->getId();
    }

    public function getCid(): ?int
    {
        return $this->task->getCid();
    }

    public function getCtype(): ?string
    {
        return $this->task->getCtype();
    }

    public function getDate(): ?int
    {
        return $this->task->getDate();
    }

    public function getAction(): ?string
    {
        return $this->task->getAction();
    }

    public function getVersion(): ?int
    {
        return $this->task->getVersion();
    }
}
