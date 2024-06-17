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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'User',
    description: 'Contains all information about a user',
    type: 'object'
)]
final class User implements AdditionalAttributesInterface
{

    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Unique Identifier', type: 'integer', example: '1')]
        private readonly int $id,
        #[Property(description: 'Name of Folder or User', type: 'string', example: 'admin')]
        private readonly string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
