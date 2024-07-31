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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

#[Schema(
    title: 'EmailLogParameters',
    required: ['name', 'value', 'objectData'],
    type: 'object'
)]
final class EmailLogEntryParameter implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'name', type: 'string', example: 'myParameter')]
        private readonly string $name,
        #[Property(description: 'value', type: 'string', example: 'Some value')]
        private readonly ?string $value = null,
        #[Property(description: 'data for object parameters', type: ObjectParameter::class)]
        private readonly ?ObjectParameter $objectData = null,
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getObjectData(): ?ObjectParameter
    {
        return $this->objectData;
    }
}
