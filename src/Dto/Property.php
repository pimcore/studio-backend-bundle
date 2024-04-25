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

readonly class Property
{
    public function __construct(private \Pimcore\Model\Property $property)
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
