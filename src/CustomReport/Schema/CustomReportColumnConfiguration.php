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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Custom Report Column Configuration',
    type: 'object',
)]
final readonly class CustomReportColumnConfiguration
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'attributesAvailable')]
        private string $name,
        #[Property(description: 'Display name of column', type: 'bool', example: true)]
        private bool $display,
        #[Property(description: 'Whether the column should be included in exports', type: 'bool', example: true)]
        private bool $export,
        #[Property(description: 'Order', type: 'bool', example: true)]
        private bool $order,
        #[Property(description: 'Label/display name of column', type: 'string', example: 'Attributes')]
        private string $label,
        #[Property(description: 'Id', type: 'string', example: '401-3')]
        private string $id,
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    public function getExport(): bool
    {
        return $this->export;
    }

    public function getOrder(): bool
    {
        return $this->order;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
