<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service;

final class IconService implements IconServiceInterface
{
    private string $defaultIcon = 'file-question-02';

    public function getIconForAsset(string $assetType, ?string $mimeType): string
    {
        if ($assetType === 'document' && $mimeType !== null) {
            return match ($mimeType) {
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation-chart-01',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-x-03',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-02',
                'application/pdf' => 'file-check-02',
                default => $this->defaultIcon
            };
        }

        if ($assetType === 'text' && $mimeType !== null) {
            return match ($mimeType) {
                'application/json' => 'file-code-01',
                'application/type9' => 'file-check-02',
                'text/plain' => 'file-02',
                'text/csv' => 'file-x-03',
                default => $this->defaultIcon
            };
        }

        return match ($assetType) {
            'folder' => 'folder',
            'image' => 'image-01',
            'video' => 'video-recorder',
            'audio' => 'volume-max',
            default => $this->defaultIcon
        };
    }
}
