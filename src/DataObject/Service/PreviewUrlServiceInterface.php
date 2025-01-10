<?php

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PreviewParameter;

interface PreviewUrlServiceInterface
{
    public function getPreviewUrl(PreviewParameter $previewParameter): string;
}