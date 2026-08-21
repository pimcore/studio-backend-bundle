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
 * Which state a read path renders: published, or the user's unsaved edits. Default is
 * Pimcore's newest unpublished version; decorate to store pending edits elsewhere.
 *
 * @internal
 */
interface DraftElementResolverInterface
{
    /** $element as $user should see it. Must not mutate $element — it is the runtime-cached instance. */
    public function resolve(ElementInterface $element, ?UserInterface $user): ResolvedDraft;
}
