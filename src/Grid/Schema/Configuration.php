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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * Contains all data to configure a grid column
 *
 * @internal
 */
#[Schema(
    title: 'GridConfiguration',
    required: ['id', 'name', 'description'],
    type: 'object'
)]
final class Configuration implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 42)]
        private readonly int $id,
        #[Property(description: 'Name', type: 'string', example: 'My Configuration')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'My Configuration Description')]
        private readonly string $description,
    ) {
    }
    public  function getId(): int
    {
        return $this->id;
    }
    public  function getName(): string
    {
        return $this->name;
    }
    public  function getDescription(): string
    {
        return $this->description;
    }
}
