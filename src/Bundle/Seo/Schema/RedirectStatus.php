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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'BundleSeoRedirectStatus',
    title: 'Bundle Seo Redirect Status',
    required: ['code', 'label'],
    type: 'object'
)]
final class RedirectStatus implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Status code', type: 'integer', example: 301)]
        public readonly int $code,
        #[Property(description: 'Status label', type: 'string', example: 'Moved Permanently')]
        public readonly string $label,
    ) {
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
