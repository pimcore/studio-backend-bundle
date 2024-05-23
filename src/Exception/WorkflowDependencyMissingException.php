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

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

/**
 * @internal
 */
final class WorkflowDependencyMissingException extends AbstractApiException
{
    public function __construct(string $executable)
    {
        parent::__construct(
            400,
            sprintf(
                'Please install the "%s" console executable on the server to render the workflow graph.',
                $executable
            )
        );
    }
}
