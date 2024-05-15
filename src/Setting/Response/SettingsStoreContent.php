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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Response;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'SettingsStoreContentResponse',
    title: 'SettingsStoreContentResponse',
    description: 'SettingsStoreContentResponse Scheme for API',
    type: 'object'
)]
final readonly class SettingsStoreContent
{
    public function __construct(
        #[Property(description: 'id', type: 'string', example: 'abc-123')]
        private string $id,
        #[Property(description: 'scope', type: 'string', example: 'scope_of_setting')]
        private string $scope,
        #[Property(description: 'data', type: 'array', items: new Items(additionalProperties: true))]
        private array $data
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getData(): array
    {
        return $this->data;
    }
}