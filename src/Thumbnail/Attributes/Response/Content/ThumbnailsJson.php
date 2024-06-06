<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Attributes\Response\Content;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;

/**
 * @internal
 */
final class ThumbnailsJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['items'],
            properties: [
                new Property(
                    'items',
                    ref: Thumbnail::class,
                    type: 'object'
                ),
            ],
            type: 'object',
        );
    }
}