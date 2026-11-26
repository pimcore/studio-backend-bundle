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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Email\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Email\Controller\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\Email\MappedParameter\EmailLogParameters;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailLogServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * `documentId` scopes the email log to one sending document, so a value that cannot be a
 * document ID has to be refused before it reaches the listing rather than silently selecting
 * nothing. The constraint lives on the DTO and is only enforced by Symfony's query-string
 * mapping, so this exercises the real controller argument through the real resolver: asserting
 * the constraint attribute in isolation would prove the annotation exists, not that the endpoint
 * applies it.
 *
 * @internal
 */
final class EmailLogParametersTest extends Unit
{
    public function testQueryStringMapsOntoTheParameters(): void
    {
        $parameters = $this->resolve('page=2&pageSize=25&documentId=42');

        $this->assertSame(2, $parameters->getPage());
        $this->assertSame(25, $parameters->getPageSize());
        $this->assertSame(42, $parameters->getDocumentId());
    }

    public function testOmittedDocumentIdLeavesTheLogUnfiltered(): void
    {
        $parameters = $this->resolve('page=1&pageSize=10');

        $this->assertNull($parameters->getDocumentId());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function invalidDocumentIdProvider(): iterable
    {
        yield 'zero' => ['documentId=0', 'This value should be positive.'];
        yield 'negative' => ['documentId=-1', 'This value should be positive.'];
        yield 'not an integer' => ['documentId=abc', 'This value should be of type int.'];
    }

    /**
     * Symfony answers 404, not 422: #[MapQueryString] defaults to
     * `validationFailedStatusCode: Response::HTTP_NOT_FOUND` and no controller in this bundle
     * overrides it. Pinned here so a change of that bundle-wide default is noticed.
     *
     * @dataProvider invalidDocumentIdProvider
     */
    public function testInvalidDocumentIdIsRejected(string $queryString, string $expectedMessage): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode(0);

        try {
            $this->resolve($queryString);
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());

            throw $exception;
        }
    }

    /**
     * Runs Symfony's query-string mapping over the real controller argument, exactly as the
     * kernel does before calling the action.
     */
    private function resolve(string $queryString): EmailLogParameters
    {
        $controller = [
            new CollectionController(
                $this->makeEmpty(SerializerInterface::class),
                $this->makeEmpty(EmailLogServiceInterface::class)
            ),
            'getEmailLogEntries',
        ];

        $request = Request::create('/pimcore-studio/api/emails?' . $queryString);
        $metadata = (new ArgumentMetadataFactory())->createArgumentMetadata($controller);
        $resolver = new RequestPayloadValueResolver($this->serializer(), $this->validator());

        $resolved = [...$resolver->resolve($request, $metadata[0])];
        $this->assertNotSame(
            [],
            $resolved,
            'The email log parameters are not mapped from the query string.'
        );

        $event = new ControllerArgumentsEvent(
            $this->makeEmpty(HttpKernelInterface::class),
            $controller,
            $resolved,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
        $resolver->onKernelControllerArguments($event);

        $mapped = $event->getArguments()[0];
        $this->assertInstanceOf(EmailLogParameters::class, $mapped);

        return $mapped;
    }

    /**
     * Built with a type extractor, like the framework's own `serializer` service. Without one a
     * query string is all strings, `documentId=abc` would be handed to the constructor as-is and
     * the non-integer case would never reach a violation.
     */
    private function serializer(): SerializerInterface&DenormalizerInterface
    {
        return new Serializer(
            [new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())],
            [new JsonEncoder()]
        );
    }

    /**
     * Attribute mapping is what the framework's own `validator` service uses by default, and it
     * is what makes the DTO's #[Positive] a constraint rather than a comment.
     */
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
