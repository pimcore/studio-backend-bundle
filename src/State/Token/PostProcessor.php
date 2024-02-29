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
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Token;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Info;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Output;
use Pimcore\Security\User\User;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * @internal
 */
final class PostProcessor implements ProcessorInterface
{
    private const TMP_STORE_ID = 'studio-api-token-user-{userId}';
    private const TMP_STORE_ID_PLACEHOLDER = '{userId}';

    private const OPERATION_URI_TEMPLATE = '/token/create';

    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TmpStoreResolverInterface $tmpStoreResolver,
        private readonly int $tokenLifetime,
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
            !$operation instanceof Post ||
            !$data instanceof Token ||
            $operation->getUriTemplate() !== self::OPERATION_URI_TEMPLATE
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        /** @var User $user */
        $user = $this->checkUserAndPassword($data);

        $token = $this->generateToken();

        $this->saveTokenToTmpStore(
            new Info(
                $token,
                $this->getTmpStoreId($user->getUserIdentifier()),
                $user->getUserIdentifier()
            )
        );

       return new Output($token, $this->tokenLifetime, $user->getUserIdentifier());
    }


    private function checkUserAndPassword($data): PasswordAuthenticatedUserInterface
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($data->getUsername());
        } catch (UserNotFoundException) {
            throw new AccessDeniedException('Invalid credentials');
        }

        if(
            !$user instanceof PasswordAuthenticatedUserInterface ||
            !$this->passwordHasher->isPasswordValid($user, $data->getPassword())
        ){
            throw new AccessDeniedException('Invalid credentials');
        }

        return $user;
    }

    private function generateToken(): string
    {
        do {
            $token = $this->tokenGenerator->generateToken();
            $ids = $this->tmpStoreResolver->getIdsByTag($token);
        } while (count($ids) > 0);

        return $token;
    }

    private function saveTokenToTmpStore(Info $tokenInfo): void
    {
        $this->tmpStoreResolver->set($tokenInfo->getTmpStoreId(), [
            'username' => $tokenInfo->getUsername(),
        ], $tokenInfo->getToken(), $this->tokenLifetime);
    }

    private function getTmpStoreId(string $userId): string
    {
        return str_replace(
            self::TMP_STORE_ID_PLACEHOLDER,
            $userId,
            self::TMP_STORE_ID
        );
    }
}