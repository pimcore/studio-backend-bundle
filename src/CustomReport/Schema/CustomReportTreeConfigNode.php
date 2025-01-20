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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Custom Report Configuration Tree Node',
    required: ['id', 'text', 'cls', 'writeable'],
    type: 'object',
)]
final class CustomReportTreeConfigNode implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'string', example: 'Quality_Attributes')]
        private readonly string $id,
        #[Property(description: 'text', type: 'string', example: 'Quality_Attributes')]
        private readonly string $text,
        #[Property(description: 'css class', type: 'string', example: 'pimcore_treenode_disabled')]
        private readonly string $cls,
        #[Property(description: 'writeable', type: 'bool', example: true)]
        private readonly bool $writeable
    ) {

    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCls(): string
    {
        return $this->cls;
    }

    public function getWriteable(): bool
    {
        return $this->writeable;
    }
}
