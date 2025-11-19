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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataProvider;


final class GdprDataProviderEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.data_provider';

    public function __construct(private readonly GdprDataProvider $provider)
    {
        parent::__construct($this->provider);
    }

    public function getProvider(): GdprDataProvider
    {
        return $this->provider;
    }
}
