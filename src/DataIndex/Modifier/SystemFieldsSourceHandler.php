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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Modifier;

use Pimcore\Bundle\GenericDataIndexBundle\Attribute\Search\AsSearchModifierHandler;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\FieldCategory;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Modifier\SearchModifierContextInterface;

/**
 * @internal
 */
final readonly class SystemFieldsSourceHandler
{
    #[AsSearchModifierHandler]
    public function handleSystemFieldsSource(
        SystemFieldsSource $systemFieldsSource,
        SearchModifierContextInterface $context
    ): void {
        $context->getSearch()->setSource([FieldCategory::SYSTEM_FIELDS->value]);
    }
}
