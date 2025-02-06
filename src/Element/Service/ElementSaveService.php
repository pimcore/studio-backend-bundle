<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * @internal
 */
final readonly class ElementSaveService implements ElementSaveServiceInterface
{
    public function __construct(private SynchronousProcessingServiceInterface $synchronousProcessingService)
    {
    }

    /**
     * @throws DuplicateFullPathException
     * @throws Exception
     */
    public function save(ElementInterface $element, UserInterface $user, ?string $task): void
    {
        $this->synchronousProcessingService->enable();
        $element->setUserModification($user->getId());

        if ($task === null) {
            $element->save();
        }

        if (!in_array($task, ElementSaveTasks::values(), true)) {
            return;
        }

        $this->processTask($element, $user, $task);
    }

    /**
     * @throws Exception
     */
    private function processTask(ElementInterface $element, UserInterface $user, string $task): void
    {
        /**
         * @var Concrete $element
         */
        $element->setOmitMandatoryCheck(true);

        $autoSave = $task === ElementSaveTasks::AUTOSAVE->value;

        $element->saveVersion(true, true, null, $autoSave);

        if ($autoSave) {
            return;
        }

        $element->deleteAutoSaveVersions($user->getId());
    }
}
