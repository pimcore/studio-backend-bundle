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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;

/**
 * @internal
 */
#[Schema(
    title: 'DocTypeUpdate',
    required: ['name', 'type', 'group', 'controller', 'template', 'priority', 'staticGeneratorEnabled'],
    type: 'object'
)]
final readonly class DocTypeUpdateParameters
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'My docType')]
        private string $name,
        #[Property(description: 'Type', type: 'string', example: DocumentTypes::PAGE->value)]
        private string $type,
        #[Property(description: 'Group', type: 'string', example: 'Default')]
        private ?string $group = null,
        #[Property(
            description: 'Controller',
            type: 'string',
            example: 'App\\Controller\\DefaultController::indexAction'
        )]
        private ?string $controller = null,
        #[Property(description: 'Template', type: 'string', example: '@App/Resources/views/default.html.twig')]
        private ?string $template = null,
        #[Property(description: 'Priority', type: 'integer', example: 0)]
        private int $priority = 0,
        #[Property(description: 'Static generator enabled', type: 'boolean', example: false)]
        private bool $staticGeneratorEnabled = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isStaticGeneratorEnabled(): bool
    {
        return $this->staticGeneratorEnabled;
    }
}
