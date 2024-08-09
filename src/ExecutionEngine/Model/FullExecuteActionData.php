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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model;

use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class FullExecuteActionData extends ExecuteActionData
{
    public function __construct(
        private ElementDescriptor $subject,
        UserInterface $user,
        array $environmentData = []
    ) {
        parent::__construct($user, $environmentData);

    }

    public function getSubject(): ElementDescriptor
    {
        return $this->subject;
    }
}
