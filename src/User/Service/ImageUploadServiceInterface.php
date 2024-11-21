<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
interface ImageUploadServiceInterface
{
    public function uploadUserImage(UploadedFile $file, int $userId): void;
}