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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ResolvedDraft;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * Which state a read path should render: published, or the user's unsaved edits.
 *
 * Default is Pimcore's own — the newest unpublished version for that user. Decorate to store
 * pending edits elsewhere; every read path follows, instead of each needing to be taught.
 *
 * @internal
 */
interface DraftElementResolverInterface
{
    /**
     * $element as $user should see it: their draft state if any, otherwise unchanged.
     *
     * Must not mutate $element — several of these run per request, and $element is usually the
     * runtime-cached instance the write path will go on to use. (Loading a version payload does
     * clear Pimcore's runtime cache via Version::loadData(), so this is not a promise that
     * calling it is free; it is a promise that the argument comes back untouched.)
     */
    public function resolve(ElementInterface $element, ?UserInterface $user): ResolvedDraft;
}
