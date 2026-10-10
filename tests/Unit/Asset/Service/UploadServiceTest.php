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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UploadServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testUploadAssetMapsValidationExceptionToFieldValidationFailure(): void
    {
        $validation = new ValidationException('Image too large');
        $service = $this->createService($validation);
        $file = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($file, 'content');

        try {
            $service->uploadAsset(1, 'image.jpg', $file, $this->makeEmpty(UserInterface::class, ['getId' => 1]));
            $this->fail('Expected FieldValidationFailedException');
        } catch (FieldValidationFailedException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame($validation, $e->getPrevious());
            $this->assertSame('Image too large', $e->getMessage());
        } finally {
            @unlink($file);
        }
    }

    /**
     * @throws Exception
     */
    public function testUploadAssetKeepsMappingOtherExceptionsToDatabaseException(): void
    {
        $service = $this->createService(new Exception('boom'));
        $file = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($file, 'content');

        try {
            $this->expectException(DatabaseException::class);
            $service->uploadAsset(1, 'image.jpg', $file, $this->makeEmpty(UserInterface::class, ['getId' => 1]));
        } finally {
            @unlink($file);
        }
    }

    /**
     * @throws Exception
     */
    private function createService(Exception $createException): UploadService
    {
        $folder = $this->makeEmpty(Folder::class, ['isAllowed' => true, 'getRealFullPath' => '/']);

        return new UploadService(
            $this->makeEmpty(AssetServiceInterface::class, [
                'getAssetElement' => $folder,
                'getUniqueAssetName' => 'image.jpg',
            ]),
            $this->makeEmpty(AssetResolverInterface::class, [
                'create' => static function () use ($createException): never {
                    throw $createException;
                },
            ]),
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(JobExecutionAgentInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class, ['getValidKey' => 'image.jpg']),
            $this->makeEmpty(StorageServiceInterface::class),
            $this->makeEmpty(SynchronousProcessingServiceInterface::class),
        );
    }
}
