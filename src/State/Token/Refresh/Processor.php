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

namespace Pimcore\Bundle\StudioApiBundle\State\Token\Refresh;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Output;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Refresh;
use Pimcore\Bundle\StudioApiBundle\Service\TokenServiceInterface;

/**
 * @internal
 */
final class Processor implements ProcessorInterface
{
    private const OPERATION_URI_TEMPLATE = '/token/refresh';

    public function __construct(
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
            !$data instanceof Refresh ||
            $operation->getUriTemplate() !== self::OPERATION_URI_TEMPLATE
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        $tokenInfo = $this->tokenService->refreshToken($data->getToken());

        return new Output($tokenInfo->getToken(), $this->tokenService->getLifetime(), $tokenInfo->getUsername());
    }
}
