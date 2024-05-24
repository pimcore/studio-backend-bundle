<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\UpdateAdapter;

use Pimcore\Model\Element\ElementInterface;

interface UpdateAdapterInterface
{
    public function update(ElementInterface $element, array $data);

    public function getDataIndex(): string;
}