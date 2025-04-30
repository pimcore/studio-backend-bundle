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
