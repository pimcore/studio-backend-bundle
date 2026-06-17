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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Util\Trait;

use Pimcore\Model\Tool\Email\Log;

/**
 * @internal
 */
trait EmailLogFieldTrait
{
    /**
     * The "from" property on the Log model is a non-nullable typed property without a default, so calling
     * getFrom() on a log entry with a NULL "from" column throws an uninitialized property error.
     * Reading it via getObjectVars() avoids that.
     */
    private function getFromAddress(Log $entry): ?string
    {
        return $entry->getObjectVars()['from'] ?? null;
    }

    /**
     * The "subject" property is non-nullable without a default, like "from", so getSubject() throws on a
     * log entry with a NULL "subject" column. Reading it via getObjectVars() avoids that.
     */
    private function getSubjectLine(Log $entry): ?string
    {
        return $entry->getObjectVars()['subject'] ?? null;
    }
}
