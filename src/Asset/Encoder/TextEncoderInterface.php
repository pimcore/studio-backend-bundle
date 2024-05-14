<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Encoder;

use Pimcore\Model\Element\ElementInterface;

interface TextEncoderInterface
{
    public function encodeUTF8(ElementInterface $element): string;
}