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
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsUpdate;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\ElementParameter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class WebsiteSettingsUpdateDenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === WebsiteSettingsUpdate::class;
    }

    public function denormalize($data, $type, $format = null, array $context = []): WebsiteSettingsUpdate
    {
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
