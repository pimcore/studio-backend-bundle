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

use Pimcore\Model\UserInterface;

/**
 * @internal
 */
readonly class ExecuteActionData
{
    public function __construct(
        private UserInterface $user,
        private array $environmentData = []
    ) {

    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }
}
