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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\StorageDirectories;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
final readonly class StorageService implements StorageServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private StorageResolverInterface $storageResolver,
    ) {
    }

    /**
     * @throws EnvironmentException
     */
    public function removeFile(string $location): void
    {
        $storage = $this->getTempStorage();
        try {
            $storage->delete($location);
        } catch (FilesystemException $e) {
            throw new EnvironmentException(
                sprintf(
                    'Could not remove file %s: %s',
                    $location,
                    $e->getMessage()
                )
            );
        }

    }

    public function getTempStorage(): FilesystemOperator
    {
        return $this->storageResolver->get(StorageDirectories::TEMP->value);
    }
}
