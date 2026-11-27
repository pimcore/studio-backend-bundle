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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Exception\Api;

use Exception;
use Pimcore\Model\Element\ValidationException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * What can fail a save, and whether the API exception keeps it as its previous exception. Only a validation
 * refusal is kept: Symfony's firewall and the API subscriber answer other previous exceptions by their type,
 * which would change the response.
 *
 * @internal
 */
trait FailureCauseProviderTrait
{
    /**
     * @return array<string, array{Exception, bool}>
     */
    public static function failureCauses(): array
    {
        return [
            'validation refusal' => [new ValidationException('Empty mandatory field [ name ]'), true],
            'any other failure' => [new RuntimeException('Connection lost'), false],
            'access denied' => [new AccessDeniedException('Nope'), false],
            'authentication' => [new AuthenticationException('Nope'), false],
            'Symfony validator' => [new ValidationFailedException(null, new ConstraintViolationList()), false],
        ];
    }
}
