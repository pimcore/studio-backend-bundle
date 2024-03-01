<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\State\Token\Create;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Create;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Output;
use Pimcore\Bundle\StudioApiBundle\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\TokenServiceInterface;
use Pimcore\Security\User\User;

/**
 * @internal
 */
final class Processor implements ProcessorInterface
{
    private const OPERATION_URI_TEMPLATE = '/token/create';

    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly TokenServiceInterface $tokenService,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Output {
        if (
            !$operation instanceof Post ||
            !$data instanceof Create ||
            $operation->getUriTemplate() !== self::OPERATION_URI_TEMPLATE
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        /** @var User $user */
        $user = $this->securityService->authenticateUser($data);

        $token = $this->tokenService->generateAndSaveToken($user->getUserIdentifier());

        return new Output($token, $this->tokenService->getLifetime(), $user->getUserIdentifier());
    }
}
