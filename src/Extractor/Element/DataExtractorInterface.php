<?php

namespace Pimcore\Bundle\StudioBackendBundle\Extractor\Element;

use Pimcore\Model\Element\ElementInterface;

interface DataExtractorInterface
{
    public function extractData(ElementInterface $element): array;
}