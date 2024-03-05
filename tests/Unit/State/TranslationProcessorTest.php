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

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\State;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioApiBundle\State\TranslationProcessor;
use stdClass;

final class TranslationProcessorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testWrongUriTemplate(): void
    {
        $translationProcessor = $this->mockTranslationProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            $this->getTranslation(),
            $this->getPostOperation('/wrong-uri-template')
        );
    }

    /**
     * @throws Exception
     */
    public function testWrongOperation(): void
    {
        $translationProcessor = $this->mockTranslationProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            $this->getTranslation(),
            $this->getGetOperation()
        );
    }

    /**
     * @throws Exception
     */
    public function testWrongData(): void
    {
        $translationProcessor = $this->mockTranslationProcessor();

        $this->expectException(OperationNotFoundException::class);

        $translationProcessor->process(
            new stdClass(),
            $this->getPostOperation('/translations')
        );
    }

    /**
     * @throws Exception
     */
    private function mockTranslationProcessor(): TranslationProcessor
    {
        $translatorInterface = $this->makeEmpty(TranslatorServiceInterface::class);

        return new TranslationProcessor($translatorInterface);
    }

    private function getTranslation(): Translation
    {
        return new Translation('en', []);
    }

    private function getPostOperation(string $uriTemplate): Post
    {
        return new Post(uriTemplate: $uriTemplate);
    }

    private function getGetOperation(): Get
    {
        return new Get(uriTemplate: '/translations');
    }
}
