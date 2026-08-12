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
     * Must be side-effect free — several run per request, and $element is usually the
     * runtime-cached instance the write path will go on to use.
     */
    public function resolve(ElementInterface $element, ?UserInterface $user): ElementInterface;
}
