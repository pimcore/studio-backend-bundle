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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Request;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\ElementParameter;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsUpdate;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_array;
use function is_bool;
use function is_string;

final readonly class WebsiteSettingsUpdateDenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === WebsiteSettingsUpdate::class;
    }

    public static function getSupportedTypes(?string $format): array
    {
        return [
            WebsiteSettingsUpdate::class => true,
        ];
    }

    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): WebsiteSettingsUpdate {
        $rawData = $data['data'] ?? null;

        if (is_array($rawData)) {
            $rawData = new ElementParameter(
                $rawData['id'] ?? null,
                $rawData['fullPath'] ?? null
            );
        } elseif (!is_string($rawData) && !is_bool($rawData) && $rawData !== null) {
            throw new InvalidArgumentException('Unsupported `data` type');
        }

        return new WebsiteSettingsUpdate(
            name: $data['name'],
            language: $data['language'],
            data: $rawData,
            siteId: $data['siteId'] ?? null
        );
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return false;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): null
    {
        return null;
    }
}
