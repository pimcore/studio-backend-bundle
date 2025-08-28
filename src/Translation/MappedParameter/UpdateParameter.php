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

use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;

/**
 * @internal
 */
final readonly class UpdateParameter
{
    public function __construct(
        /** @var UpdateTranslation[] $data */
        private array $data
    ) {
    }

    /**
     * @return UpdateTranslation[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
