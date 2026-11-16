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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

/**
 * A provider whose list, mode and default the test sets.
 *
 * Core resolves a provider by class name and holds the instance in a process-wide cache
 * ({@see \Pimcore\Model\DataObject\ClassDefinition\Helper\ClassResolver}), so what a test varies
 * has to live on the class rather than on an instance it hands over.
 *
 * @internal
 */
final class ConfigurableOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public static array $options = [];

    public static bool $staticOptions = true;

    /**
     * @var string[]|string|null
     */
    public static string|array|null $defaultValue = null;

    public static function reset(): void
    {
        static::$options = [];
        static::$staticOptions = true;
        static::$defaultValue = null;
    }

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        return static::$options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return static::$staticOptions;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): string|array|null
    {
        return static::$defaultValue;
    }
}
