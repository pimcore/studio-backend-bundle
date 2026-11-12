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

use Exception;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ElementSaveServiceInterface
{
    public const string INDEX_TASK = 'task';

    public const string INDEX_COAUTHOR_TYPE = 'coauthorType';

    public const string INDEX_COAUTHOR = 'coauthor';

    /**
     * @throws Exception|DuplicateFullPathException|ValidationException
     */
    public function save(ElementInterface $element, UserInterface $user, ?string $task = null): void;
}
