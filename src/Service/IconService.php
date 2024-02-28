<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service;

final class IconService implements IconServiceInterface
{
    public function getIconForAsset(string $assetType, ?string $mimeType): string
    {
        if ($assetType === 'document' && $mimeType !== null) {
            return match ($mimeType) {
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation-chart-01',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-x-03',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-02',
                'application/pdf' => 'file-check-02',
                default => 'file'
            };
        }

        if ($assetType === 'text' && $mimeType !== null) {
            return match ($mimeType) {
                'application/json' => 'file-code-01',
                'application/type9' => 'file-check-02',
                'text/plain' => 'file-02',
                default => 'file'
            };
        }

        return match ($assetType) {
            'folder' => 'folder',
            'image' => 'image-01',
            'video' => 'video-recorder',
            'audio' => 'volume-max',
            default => 'file-question-02'
        };
    }
}