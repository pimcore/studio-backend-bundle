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

namespace Pimcore\Bundle\StudioBackendBundle\Event;

use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
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
