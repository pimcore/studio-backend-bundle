<?php

namespace Pimcore\Bundle\StudioBackendBundle\Extractor\Element;

use Pimcore\Model\Element\ElementInterface;

class DataExtractor implements DataExtractorInterface
{
    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    public function extractData(ElementInterface $element): array
    {
        $data = array_intersect_key(
            $element->getObjectVars(),
            array_flip(self::ALLOWED_MODEL_PROPERTIES)
        );

        $data['fullPath'] = $element->getFullPath();
        return $data;

    }
}