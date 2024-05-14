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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Request;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'SettingsRequest',
    title: 'SettingsRequest',
    description: 'SettingsRequest Scheme for API',
    type: 'object'
)]
final readonly class SettingsRequest
{
    public function __construct(
        #[Property(description: 'parameters', type: 'array', items: new Items(
            type: 'string', example: 'not_your_typical_key'
        ))]
        private array $parameters = []
    ) {
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
