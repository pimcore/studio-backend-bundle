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
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;

/**
 * @internal
 */
final readonly class LogHydrator implements LogHydratorInterface
{
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
            relatedObjectId: $log['relatedobject'],
            relatedObjectType: $log['relatedobjecttype'],
            component: $log['component'],
            source: $log['source']
        );
    }
}
