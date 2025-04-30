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

namespace Pimcore\Bundle\StudioBackendBundle\User\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;

final class UserInformationEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.user_information';

    public function __construct(private readonly UserInformation $userInformation)
    {
        parent::__construct($this->userInformation);
    }

    public function getUserInformation(): UserInformation
    {
        return $this->userInformation;
    }
}
