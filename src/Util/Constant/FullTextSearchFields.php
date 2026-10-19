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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * Fields searched by the full text search.
 * Without an explicit list the search index expands the query over every mapped field,
 * which fails on indices with a very large number of fields.
 *
 * @internal
 */
final readonly class FullTextSearchFields
{
    public const array FIELDS = [
        'system_fields.key',
        'system_fields.key.analyzed',
        'system_fields.key.analyzed_ngram',
        'system_fields.text.analyzed',
        'system_fields.text.analyzed_ngram',
        'standard_fields.*.analyzed',
        'standard_fields.*.analyzed_ngram',
        'custom_fields.*.analyzed',
        'custom_fields.*.analyzed_ngram',
    ];
}
