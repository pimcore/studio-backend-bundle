<?php

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Token;

use ApiPlatform\State\ProcessorInterface;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioApiBundle\State\Token\PostProcessor;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * @internal
 */
final class PostProcessorTest extends Unit
{
    private const TOKEN_TTL = 3600;
    public function testTokenCreate()
    {
        $processor = $this->mockProcessor();
        $this->assertInstanceOf(ProcessorInterface::class, $processor);
    }

    private function mockProcessor(): ProcessorInterface
    {
        return new PostProcessor(
            $this->mockUserProvider(),
            $this->mockTokenGenerator(),
            $this->mockPasswordHasher(),
            $this->mockTmpStoreResolver(),
            self::TOKEN_TTL
        );
    }

    private function mockUserProvider(): UserProvider {
        return $this->makeEmpty(UserProvider::class);
    }

    private function mockTokenGenerator(): TokenGeneratorInterface
    {
        return $this->makeEmpty(TokenGeneratorInterface::class);
    }

    private function mockPasswordHasher(): UserPasswordHasherInterface
    {
        return $this->makeEmpty(UserPasswordHasherInterface::class);
    }

    private function mockTmpStoreResolver(): TmpStoreResolverInterface
    {
        return $this->makeEmpty(TmpStoreResolverInterface::class);
    }
}