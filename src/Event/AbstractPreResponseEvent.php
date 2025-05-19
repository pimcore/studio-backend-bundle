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

namespace Pimcore\Bundle\StudioBackendBundle\Event;

use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractPreResponseEvent extends Event
{
    public function __construct(protected readonly AdditionalAttributesInterface $responseObject)
    {
    }

    public function hasAdditionalAttribute(string $key): bool
    {
        return $this->responseObject->hasAdditionalAttribute($key);
    }

    public function getAdditionalAttribute(string $key): mixed
    {
        return $this->responseObject->getAdditionalAttribute($key);
    }

    public function addAdditionalAttribute(string $key, mixed $value): void
    {
        $this->responseObject->addAdditionalAttribute($key, $value);
    }

    public function removeAdditionalAttribute(string $key): void
    {
        $this->responseObject->removeAdditionalAttribute($key);
    }
}
