<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\User;
use Pimcore\Model\Version as ModelVersion;

class Version
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
        $data = $this->version->getData();
        if ($data instanceof Image) {
            return new \Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image($data, new Permissions());
        }

        return $data;
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
