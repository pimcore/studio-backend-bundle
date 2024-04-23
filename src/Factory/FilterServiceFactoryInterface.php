<?php

namespace Pimcore\Bundle\StudioApiBundle\Factory;

interface FilterServiceFactoryInterface
{
    public function create(string $type): mixed;
}