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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Hydrator;

use Carbon\Carbon;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class LogHydrator implements LogHydratorInterface
{
    public function __construct(
        private ElementDataServiceInterface $elementDataService,
        private ElementServiceInterface $elementService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function hydrate(array $log): LogEntry
    {
        $fileObject = null;
        if ($log['fileobject']) {
            $fileObject = str_replace(PIMCORE_PROJECT_ROOT, '', $log['fileobject']);
        }

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $log['timestamp'], 'UTC');

        return new LogEntry(
            id: $log['id'],
            priority: $log['priority_value'],
            date: $date?->toIso8601String(),
            pid: $log['pid'],
            message: $log['message'],
            fileObject: $fileObject,
            relatedElementData: $this->getElementData($log),
            component: $log['component'],
            source: $log['source']
        );
    }

    private function getElementData(array $log): ?RelatedElementData
    {
        if (empty($log['relatedobject']) || empty($log['relatedobjecttype'])) {
            return null;
        }

        try {
            $element = $this->elementService->getAllowedElementById(
                $log['relatedobjecttype'],
                $log['relatedobject'],
                $this->securityService->getCurrentUser()
            );
        } catch (Exception) {
            return null;
        }

        return $this->elementDataService->getRelatedElementData($element);
    }
}
