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

use Pimcore\Model\User;
use Pimcore\Model\Version as ModelVersion;

readonly class Version
{
    public function __construct(private ModelVersion $version)
    {
    }

    public function getBinaryFileStream(): mixed
    {
        return $this->version->getBinaryFileStream();
    }

    public function getId(): ?int
    {
        return $this->version->getId();
    }

    public function getCid(): int
    {
        return $this->version->getCid();
    }

    public function getCtype(): string
    {
        return $this->version->getCtype();
    }

    public function getDate(): int
    {
        return $this->version->getDate();
    }

    public function getFileStream(): mixed
    {
        return $this->version->getFileStream();
    }

    public function getNote(): string
    {
        return $this->version->getNote();
    }

    public function getUserId(): int
    {
        return $this->version->getUserId();
    }

    /**
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->version->getData();
        //        if ($data instanceof Image) {
        //            return new Asset\Image($data, new Permissions());
        //        }
    }

    public function getSerialized(): bool
    {
        return $this->version->getSerialized();
    }

    public function getUser(): ?User
    {
        return $this->version->getUser();
    }

    public function isPublic(): bool
    {
        return $this->version->isPublic();
    }

    public function getVersionCount(): int
    {
        return $this->version->getVersionCount();
    }

    public function getBinaryFileHash(): ?string
    {
        return $this->version->getBinaryFileHash();
    }

    public function getBinaryFileId(): ?int
    {
        return $this->version->getBinaryFileId();
    }

    public function isAutoSave(): bool
    {
        return $this->version->isAutoSave();
    }

    public function getStorageType(): ?string
    {
        return $this->version->getStorageType();
    }
}
