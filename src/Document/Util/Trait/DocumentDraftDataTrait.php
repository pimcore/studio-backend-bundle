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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait;

use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippetDraftData;

/**
 * @internal
 */
trait DocumentDraftDataTrait
{
    #[Property(ref: PageSnippetDraftData::class)]
    private ?PageSnippetDraftData $draftData = null;

    public function getDraftData(): ?PageSnippetDraftData
    {
        return $this->draftData;
    }

    public function setDraftData(?PageSnippetDraftData $draftData = null): void
    {
        $this->draftData = $draftData;
    }
}
