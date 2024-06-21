<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model;

use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 */
final readonly class AbortActionData
{
    public function __construct(
        private string $translationKey,
        private array $translationParameters,
        private string $exceptionClassName = NotFoundException::class
    ) {

    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function getExceptionClassName(): string
    {
        return $this->exceptionClassName;
    }
}
