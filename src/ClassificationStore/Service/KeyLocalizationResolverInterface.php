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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * The one place a key's own `localized` flag is read and written, so that the read surfaces, the
 * update endpoint and the configuration transfer cannot drift apart on what it means.
 *
 * The flag is a tri-state and is published raw: `true` and `false` are the key's own answer, and
 * NULL means the key states none, so the `localized` flag of the class definition field the key is
 * rendered in decides. Nothing resolves it server side - the layout routes serve a request without
 * an object as well, where there is no class field to resolve against.
 *
 * @internal
 */
interface KeyLocalizationResolverInterface
{
    /**
     * The flag the key stores, or null where it stores none.
     */
    public function getLocalized(KeyConfig $keyConfig): ?bool;

    /**
     * Writes the flag onto the key, null included: null is a value of its own here and is never the
     * same as not writing at all.
     */
    public function setLocalized(KeyConfig $keyConfig, ?bool $localized): void;
}
