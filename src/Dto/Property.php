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

class Property
{
    public function __construct(private readonly \Pimcore\Model\Property $property)
    {
    }

    public function getCid(): ?int
    {
        return $this->property->getCid();
    }

    /**
     * enum('document','asset','object')
     */
    public function getCtype(): ?string
    {
        return $this->property->getCtype();
    }

    public function getData(): mixed
    {
        return $this->property->getData();
    }

    public function getName(): ?string
    {
        return $this->property->getName();
    }

    /**
     * enum('text','document','asset','object','bool','select')
     */
    public function getType(): ?string
    {
        return $this->property->getType();
    }

    public function getCpath(): ?string
    {
        return $this->property->getCpath();
    }

    public function isInherited(): bool
    {
        return $this->property->isInherited();
    }

    public function getInheritable(): bool
    {
        return $this->property->getInheritable();
    }
}
