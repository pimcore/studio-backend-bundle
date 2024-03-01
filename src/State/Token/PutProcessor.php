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

namespace Pimcore\Bundle\StudioApiBundle\State\Token;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Info;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Output;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Refresh;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;


/**
 * @internal
 */
final class PutProcessor implements ProcessorInterface
{
    private const OPERATION_URI_TEMPLATE = '/token/refresh';

    public function __construct(
        private readonly TmpStoreResolverInterface $tmpStoreResolver,
        private readonly int $tokenLifetime
    )
    {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Output
    {
        if (
            !$operation instanceof Put ||
            !$data instanceof Refresh ||
            $operation->getUriTemplate() !== self::OPERATION_URI_TEMPLATE
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        $tokenInfo = $this->getTmpStoreIdByToken($data->getToken());

        $this->saveTokenToTmpStore($tokenInfo);

        return new Output($data->getToken(), $this->tokenLifetime, $tokenInfo->getUsername());
    }


    private function getTmpStoreIdByToken(string $token): Info
    {
        $userIds = $this->tmpStoreResolver->getIdsByTag($token);
        if(count($userIds) === 0 || count($userIds) > 1) {
            throw new TokenNotFoundException('Token not found');
        }
        /** @var TmpStore $entry */
        $entry = $this->tmpStoreResolver->get($userIds[0]);

        $data = $entry->getData();

        if(!isset($data['username'])) {
            throw new TokenNotFoundException('Token not found');
        }

        return new Info($token, $entry->getId(), $data['username']);
    }


    private function saveTokenToTmpStore(Info $tokenInfo): void
    {
        $this->tmpStoreResolver->set($tokenInfo->getTmpStoreId(), [
            'username' => $tokenInfo->getUsername()
        ], $tokenInfo->getToken(), $this->tokenLifetime);
    }
}