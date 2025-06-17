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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WebsiteSettingTypes;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[Schema(
    schema: 'WebsiteSettingsAdd',
    title: 'Website Settings Add',
    required: ['name', 'type'],
    type: 'object'
)]
final readonly class WebsiteSettingsAdd
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'New Custom Setting')]
        private string $name,
        #[Property(description: 'Type', type: 'string', example: WebsiteSettingTypes::DOCUMENT->value)]
        private string $type = WebsiteSettingTypes::DOCUMENT->value,
    ) {
        if (!in_array($this->type, WebsiteSettingTypes::values(), true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid type "%s" provided. Allowed types are: %s',
                    $this->type,
                    implode(', ', WebsiteSettingTypes::values())
                )
            );
        }

        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
