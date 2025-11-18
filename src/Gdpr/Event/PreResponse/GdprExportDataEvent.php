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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
final class GdprExportDataEvent extends Event
{
    public const string EVENT_NAME = 'pre_response.gdpr_export_data';

    public function __construct(private array|object $data)
    {

    }

    public function getData(): array|object
    {
        return $this->data;
    }

    public function setData(array|object $data): void
    {
        $this->data = $data;
    }
}
