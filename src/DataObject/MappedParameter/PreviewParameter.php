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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Data Object Preview Parameters',
    required: ['id'],
    type: 'object'
)]
final readonly class PreviewParameter
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private int $id,
        #[Property(description: 'Site', type: 'integer', default: 0, example: 1)]
        private int $site = 0
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): int
    {
        return $this->site;
    }
}
