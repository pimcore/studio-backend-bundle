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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait\DocumentDraftDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait\DocumentEditableDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait\DocumentSettingsDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\WorkflowAvailableTrait;

#[Schema(
    title: 'Document Detail Data',
    required: ['editableData', 'missingRequiredEditable', 'settingsData', 'draftData', 'hasWorkflowAvailable'],
    type: 'object',
)]
final class DocumentDetail extends Document
{
    use DocumentEditableDataTrait;
    use DocumentSettingsDataTrait;
    use DocumentDraftDataTrait;
    use WorkflowAvailableTrait;
}
