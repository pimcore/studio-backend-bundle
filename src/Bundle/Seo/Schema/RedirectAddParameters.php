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
use Pimcore\Bundle\SeoBundle\Model\Redirect as CoreRedirect;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleSeoRedirectAdd',
    title: 'Bundle Seo Redirect Add',
    required: ['type', 'source', 'target'],
    type: 'object'
)]
final readonly class RedirectAddParameters
{
    public function __construct(
        #[Property(description: 'Type of redirect', type: 'string', example: CoreRedirect::TYPE_ENTIRE_URI)]
        public string $type = CoreRedirect::TYPE_ENTIRE_URI,
        #[Property(description: 'Source URL', type: 'string', example: '/old-path')]
        public ?string $source = null,
        #[Property(description: 'Target URL', type: 'string', example: '/new-path')]
        public ?string $target = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }
}
