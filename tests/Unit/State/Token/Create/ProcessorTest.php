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

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\State\Token\Create;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Create;
use Pimcore\Bundle\StudioApiBundle\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\TokenServiceInterface;
use Pimcore\Bundle\StudioApiBundle\State\Token\Create\Processor;
use stdClass;

final class ProcessorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testWrongUriTemplate(): void
    {
        $translationProcessor = $this->mockProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            $this->getCreateToken(),
            $this->getPostOperation('/wrong-uri-template')
        );
    }

    /**
     * @throws Exception
     */
    public function testWrongOperation(): void
    {
        $translationProcessor = $this->mockProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            $this->getCreateToken(),
            $this->getGetOperation()
        );
    }

    /**
     * @throws Exception
     */
    public function testWrongData(): void
    {
        $translationProcessor = $this->mockProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            new stdClass(),
            $this->getPostOperation('/token/create')
        );
    }

    /**
     * @throws Exception
     */
    private function mockProcessor(): Processor
    {
        return new Processor($this->mockSecuritySercice(), $this->mockTokenService());
    }

    /**
     * @throws Exception
     */
    private function mockSecuritySercice(): SecurityServiceInterface
    {
        return $this->makeEmpty(SecurityServiceInterface::class);
    }

    /**
     * @throws Exception
     */
    private function mockTokenService(): TokenServiceInterface
    {
        return $this->makeEmpty(TokenServiceInterface::class);
    }

    private function getCreateToken(): Create
    {
        return new Create('test', 'test');
    }

    private function getPostOperation(string $uriTemplate): Post
    {
        return new Post(uriTemplate: $uriTemplate);
    }

    private function getGetOperation(): Get
    {
        return new Get(uriTemplate: '/token/create');
    }
}
