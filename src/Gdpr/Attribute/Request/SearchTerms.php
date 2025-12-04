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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Search Terms',
    description: 'Object containing the values to search for. All fields are optional.',
    type: 'object'
)]
final readonly class SearchTerms
{
    public function __construct(
        #[Property(description: 'The ID to search for.', type: 'string', example: '1', nullable: true)]
        #[Type('string')]
        public ?string $id = null,

        #[Property(description: 'The first name to search for.', type: 'string', example: 'John', nullable: true)]
        #[Type('string')]
        public ?string $firstname = null,

        #[Property(description: 'The last name to search for.', type: 'string', example: 'Doe', nullable: true)]
        #[Type('string')]
        public ?string $lastname = null,

        #[Property(description: 'The email address to search for.', type: 'string', example: '', nullable: true)]
        #[Type('string')]
        public ?string $email = null,
    ) {
        if ($this->id === null &&
            $this->firstname === null &&
            $this->lastname === null &&
            $this->email === null
        ) {
            throw new InvalidArgumentException('Provide at least one search term.');
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
