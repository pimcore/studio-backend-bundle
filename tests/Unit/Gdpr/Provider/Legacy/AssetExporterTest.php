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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Gdpr\Provider\Legacy;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy\AssetExporter;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * @internal
 */
final class AssetExporterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testDoExportDataReturnsZip(): void
    {
        $asset = $this->makeEmpty(Asset::class, [
            'getId' => 5,
            'getFilename' => 'photo.jpg',
            'getRealFullPath' => '/photo.jpg',
            'getProperties' => [],
            'getCustomSettings' => [],
            'getData' => 'BINARY-CONTENT',
        ]);

        $exporter = new AssetExporter($this->makeEmpty(AssetResolverInterface::class, [
            'getById' => $asset,
        ]));

        $response = $exporter->doExportData($asset);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('photo.jpg.zip', (string) $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->headers->get('Content-Length'));

        $entries = $this->readZipEntries((string) $response->getContent());

        $this->assertArrayHasKey('photo.jpg.json', $entries);
        $this->assertArrayHasKey('photo.jpg', $entries);
        $this->assertSame('BINARY-CONTENT', $entries['photo.jpg']);
        $this->assertSame(
            ['id' => 5, 'fullpath' => '/photo.jpg', 'properties' => [], 'customSettings' => []],
            json_decode($entries['photo.jpg.json'], true)
        );
    }

    /**
     * @throws Exception
     */
    public function testDoExportDataForFolderOmitsBinaryData(): void
    {
        $folder = $this->makeEmpty(Folder::class, [
            'getId' => 7,
            'getFilename' => 'my-folder',
            'getRealFullPath' => '/my-folder',
            'getProperties' => [],
            'getCustomSettings' => [],
        ]);

        $exporter = new AssetExporter($this->makeEmpty(AssetResolverInterface::class, [
            'getById' => $folder,
        ]));

        $response = $exporter->doExportData($folder);

        $entries = $this->readZipEntries((string) $response->getContent());

        $this->assertArrayHasKey('my-folder.json', $entries);
        $this->assertArrayNotHasKey('my-folder', $entries);
    }

    /**
     * @return array<string, string> map of zip entry name => contents
     */
    private function readZipEntries(string $zipContent): array
    {
        $file = tempnam(sys_get_temp_dir(), 'gdpr-asset-export-test');

        try {
            file_put_contents($file, $zipContent);

            $zip = new ZipArchive();
            $zip->open($file);

            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $entries[$name] = $zip->getFromName($name);
            }

            $zip->close();

            return $entries;
        } finally {
            @unlink($file);
        }
    }
}
