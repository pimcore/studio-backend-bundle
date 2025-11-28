<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy;

use Pimcore\Model\Asset;

/**
 * Copied from old admin-ui-classic-bundle
 * https://github.com/pimcore/admin-ui-classic-bundle/blob/9258d42920dbb475badc1adea59a7552ab089ac4/src/GDPR/
 * DataProvider/Exporter.php
 *
 * @internal
 */
final readonly class AssetExporter implements AssetExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function doexportAsset(Asset $theAsset): array
    {
        $webAsset = [];
        $webAsset['id'] = $theAsset->getId();
        $webAsset['fullpath'] = $theAsset->getRealFullPath();
        $properties = $theAsset->getProperties();
        $finalProperties = [];

        foreach ($properties as $property) {
            $finalProperties[] = $property->serialize();
        }

        $webAsset['properties'] = $finalProperties;
        $webAsset['customSettings'] = $theAsset->getCustomSettings();

        $resultItem = json_decode(json_encode($webAsset), true);
        unset($resultItem['data']);

        return $resultItem;
    }

}
