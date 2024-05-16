<?php

namespace Pimcore\Bundle\StudioBackendBundle\Event;

/**
 * @internal
 */
interface PreResponseEventInterface
{
    public function getEventClass(): string;
}