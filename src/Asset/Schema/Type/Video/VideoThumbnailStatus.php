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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'VideoThumbnailStatus',
    title: 'Video Thumbnail Status',
    required: ['status'],
    type: 'object'
)]
final class VideoThumbnailStatus implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public const string STATUS_FINISHED = 'finished';

    public const string STATUS_INPROGRESS = 'inprogress';

    public const string STATUS_ERROR = 'error';

    public const string STATUS_NOT_STARTED = 'not_started';

    public function __construct(
        #[Property(
            description: 'Conversion status of the requested video thumbnail.',
            type: 'string',
            enum: [
                self::STATUS_FINISHED,
                self::STATUS_INPROGRESS,
                self::STATUS_ERROR,
                self::STATUS_NOT_STARTED,
            ],
            example: self::STATUS_INPROGRESS,
        )]
        private readonly string $status,
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
