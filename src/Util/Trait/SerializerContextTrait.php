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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @internal
 */
trait SerializerContextTrait
{
    private const string DEFAULT_IGNORE_ATTRIBUTE = 'childrenByRef';

    private function getSerializerContext(array $context = []): array
    {
        $ignored = $context[AbstractNormalizer::IGNORED_ATTRIBUTES] ?? [];
        $ignored = is_array($ignored) ? $ignored : [$ignored];

        if (!in_array('childrenByRef', $ignored, true)) {
            $ignored[] = 'childrenByRef';
        }

        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = $ignored;

        return $context;
    }
}
