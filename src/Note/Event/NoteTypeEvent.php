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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\NoteType;

final class NoteTypeEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.note.type';

    public function __construct(
        private readonly NoteType $noteType
    ) {
        parent::__construct($noteType);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getNote(): NoteType
    {
        return $this->noteType;
    }
}
