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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter;

/**
 * @internal
 */
final readonly class CsvSettingsParameter
{
    public function __construct(
        private string $delimiter = '',
        private string $quoteChar = '',
        private string $escapeChar = '',
        private string $lineTerminator = '',
    ) {
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function getQuoteChar(): string
    {
        return $this->quoteChar;
    }

    public function getEscapeChar(): string
    {
        return $this->escapeChar;
    }

    public function getLineTerminator(): string
    {
        return $this->lineTerminator;
    }
}
