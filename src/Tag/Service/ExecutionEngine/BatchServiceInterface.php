<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchOperationParameters;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * @internal
 */
interface BatchServiceInterface
{
    /**
     * @throws AccessDeniedException|UserNotFoundException|NotFoundException
     */
    public function createJobRunForBatchOperation(BatchOperationParameters $parameters): int;
}