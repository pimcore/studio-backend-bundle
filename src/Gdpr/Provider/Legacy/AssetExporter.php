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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy;

use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

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
    private function doexportAsset(Asset $theAsset): array
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
        unset($resultItem['data']);declare(strict_types=1);

        return $resultItem;
    }

    public function doExportData(Asset $asset): Response
    {
        $exportIds = [];
        $exportIds[$asset->getId()] = true;

        $file = tempnam('/tmp', 'zip');
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        foreach (array_keys($exportIds) as $id) {
            $theAsset = Asset::getById($id);

            $resultItem = $this->doexportAsset($theAsset);
            $resultItem = json_encode($resultItem);

            $zip->addFromString($asset->getFilename() . '.txt', $resultItem);

            if (!$theAsset instanceof Asset\Folder) {
                $zip->addFromString($theAsset->getFilename(), $theAsset->getData());
            }
        }

        $zip->close();

        $size = filesize($file);
        $content = file_get_contents($file);
        unlink($file);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) $size);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $asset->getFilename() . '.zip"');

        return $response;
    }
}
