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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @internal
 */
#[Schema(
    schema: 'ServiceUserCreateFromUser',
    title: 'Create Service User From User',
    description: 'A new service user that takes over the permissions, roles and workspaces of a user',
    required: ['name'],
    type: 'object'
)]
final readonly class CreateFromUserParameter
{
    /**
     * @param list<string>|null $scopes
     */
    public function __construct(
        #[Property(
            description: 'Unique name across users and service users',
            type: 'string',
            example: 'svc-erp-sync'
        )]
        #[NotBlank]
        #[Length(max: 64)]
        #[Regex(
            pattern: '/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
            message: 'Use letters, digits, dots, underscores and hyphens, starting with a letter or digit.'
        )]
        private string $name,
        #[Property(
            description: 'Contact email of the team responsible',
            type: 'string',
            example: 'erp-team@example.com',
            nullable: true
        )]
        #[Email]
        private ?string $email = null,
        #[Property(
            description: 'OAuth scopes the bound client may request; all known scopes when omitted',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['mcp:read'],
            nullable: true
        )]
        private ?array $scopes = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return list<string>|null
     */
    public function getScopes(): ?array
    {
        return $this->scopes;
    }
}
