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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface UpdateServiceInterface
{
    public const string USE_DRAFT_DATA_KEY = 'useDraftData';

    public const string EDITABLE_DATA_KEY = 'editableData';

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void;
}
