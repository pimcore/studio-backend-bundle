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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The payload is persisted as given, so whatever the request boundary lets through is
 * what the server is later built and authorised from.
 */
final class McpServerParameterTest extends Unit
{
    public function testAWellFormedPayloadIsValid(): void
    {
        $parameter = $this->parameter(
            tools: ['ping'],
            sharedUsers: [['name' => 'alice', 'canRead' => true, 'canAccess' => true, 'canEdit' => false]],
            sharedRoles: [['name' => 'editors', 'canAccess' => true]],
        );

        $this->assertCount(0, $this->validator()->validate($parameter));
    }

    /**
     * A non-string tool id used to be saved and then turned every read of the server into
     * a 500 once its scopes were derived from the tool registry.
     */
    public function testANonStringToolIsRejected(): void
    {
        $violations = $this->validator()->validate($this->parameter(tools: [1]));

        $this->assertCount(1, $violations);
        $this->assertSame('tools[0]', $violations[0]->getPropertyPath());
    }

    /**
     * A string "false" is non-empty, so it used to be cast to true and stored as granted.
     */
    public function testANonBooleanCapabilityIsRejected(): void
    {
        $violations = $this->validator()->validate($this->parameter(
            sharedUsers: [['name' => 'alice', 'canAccess' => 'false']],
        ));

        $this->assertCount(1, $violations);
        $this->assertSame('sharedUsers[0][canAccess]', $violations[0]->getPropertyPath());
    }

    public function testANonBooleanRoleCapabilityIsRejected(): void
    {
        $violations = $this->validator()->validate($this->parameter(
            sharedRoles: [['name' => 'editors', 'canEdit' => 1]],
        ));

        $this->assertCount(1, $violations);
        $this->assertSame('sharedRoles[0][canEdit]', $violations[0]->getPropertyPath());
    }

    public function testAGrantWithoutANameIsRejected(): void
    {
        $violations = $this->validator()->validate($this->parameter(
            sharedRoles: [['canAccess' => true]],
        ));

        $this->assertCount(1, $violations);
        $this->assertSame('sharedRoles[0][name]', $violations[0]->getPropertyPath());
    }

    /**
     * @param list<mixed> $tools
     * @param list<mixed> $sharedUsers
     * @param list<mixed> $sharedRoles
     */
    private function parameter(array $tools = [], array $sharedUsers = [], array $sharedRoles = []): McpServerParameter
    {
        return new McpServerParameter(
            name: 'Catalogue',
            urlSlug: 'catalogue',
            tools: $tools,
            sharedUsers: $sharedUsers,
            sharedRoles: $sharedRoles,
        );
    }

    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }
}
