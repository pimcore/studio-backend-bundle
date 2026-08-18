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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Model;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Version;

/**
 * The two halves of a draft, resolved together from a single lookup.
 *
 * `element` is the STATE to render. `version` is the draft IDENTITY reported as `draftData`
 * — the row the UI labels and deletes by. They coincide by default; a resolver that stores
 * pending edits elsewhere may return state without an identity.
 *
 * @internal
 */
final readonly class ResolvedDraft
{
    public function __construct(
        private ElementInterface $element,
        private ?Version $version = null,
    ) {
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    public function getVersion(): ?Version
    {
        return $this->version;
    }
}
