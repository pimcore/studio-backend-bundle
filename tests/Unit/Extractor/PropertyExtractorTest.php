<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Extractor;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Property\Extractor\PropertyDataExtractor;
use Pimcore\Bundle\StudioBackendBundle\Property\Extractor\PropertyDataExtractorInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Property;

/**
 * @internal
 */
final class PropertyExtractorTest extends Unit
{
    public function testExtractProperty(): void
    {
        $property = $this->getProperty();
        $extractor = $this->getPropertyExtractor();
        $extractedData = $extractor->extractData($property);

        $this->assertNull($extractedData['name']);
        $this->assertNull($extractedData['config']);
        $this->assertNull($extractedData['predefinedName']);
        $this->assertNull($extractedData['description']);

        $this->assertTrue($extractedData['inheritable']);
        $this->assertTrue($extractedData['inherited']);

        $this->assertSame('text', $extractedData['type']);
        $this->assertSame('/test', $extractedData['modelData']['path']);
        $this->assertSame(1, $extractedData['modelData']['id']);
        $this->assertSame('page', $extractedData['modelData']['type']);
        $this->assertSame('test', $extractedData['modelData']['key']);
    }

    private function getPropertyExtractor(): PropertyDataExtractorInterface
    {
        return new PropertyDataExtractor();
    }

    private function getProperty(): Property
    {
        $property =  new Property();
        $property->setDataFromResource($this->getDocument());
        $property->setType('text');
        $property->setCtype('document');
        $property->setInheritable(true);
        $property->setInherited(true);
        return $property;
    }

    private function getDocument(): Document
    {
        $document = new Document();
        $document->setPath('/test');
        $document->setId(1);
        $document->setType('page');
        $document->setKey('test');
        return $document;
    }
}