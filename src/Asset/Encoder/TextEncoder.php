<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Encoder;

use ForceUTF8\Encoding;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Model\Asset\Text;
use Pimcore\Model\Element\ElementInterface;

final class TextEncoder implements TextEncoderInterface
{
    private const MAX_FILE_SIZE = 2000000;
    public function encodeUTF8(ElementInterface $element): string
    {
        if(!$element instanceof Text) {
            throw new InvalidElementTypeException('Element must be an instance of Text');
        }

        if ($element->getFileSize() < self::MAX_FILE_SIZE) {
            return '';
        }

        return Encoding::toUTF8($element->getData());
    }
}