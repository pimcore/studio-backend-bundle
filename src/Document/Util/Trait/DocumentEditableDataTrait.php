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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\EditableDataInterface;

/**
 * @internal
 */
trait DocumentEditableDataTrait
{
    #[Property(description: 'Document Editable Data', type: 'object', example: ['editable' => 'value'])]
    private ?EditableDataInterface $editableData = null;

    #[Property(description: 'Is missing required editable', type: 'bool', example: false)]
    private bool $missingRequiredEditable = false;

    public function getEditableData(): ?EditableDataInterface
    {
        return $this->editableData;
    }

    public function setEditableData(?EditableDataInterface $editableData = null): void
    {
        $this->editableData = $editableData;
    }

    public function isMissingRequiredEditable(): bool
    {
        return $this->missingRequiredEditable;
    }

    public function setMissingRequiredEditable(bool $missingRequiredEditable): void
    {
        $this->missingRequiredEditable = $missingRequiredEditable;
    }
}
