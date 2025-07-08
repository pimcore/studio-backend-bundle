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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportsConfigurationTreeNode',
    title: 'Bundle Custom Reports Configuration Tree Node',
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
