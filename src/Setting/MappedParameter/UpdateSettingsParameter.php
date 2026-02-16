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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\MappedParameter;

/**
 * @internal
 */
final readonly class UpdateSettingsParameter
{
    public function __construct(
        private ?array $general = null,
        private ?array $objects = null,
        private ?array $assets = null,
        private ?array $documents = null,
        private ?array $email = null,
    ) {
    }

    public function getData(): array
    {
        return array_filter([
            'general' => $this->general,
            'objects' => $this->objects,
            'assets' => $this->assets,
            'documents' => $this->documents,
            'email' => $this->email,
        ], static fn ($value) => $value !== null);
    }
}
