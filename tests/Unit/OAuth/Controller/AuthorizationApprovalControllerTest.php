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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Controller\AuthorizationApprovalController;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\ApproveAuthorization;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationRedirect;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\AuthorizationConsentServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;
use Throwable;

/**
 * The approval endpoint decides an allow or a deny, so the interesting property is not
 * that a good body works but that a bad one is refused. These drive Symfony's real
 * #[MapRequestPayload] resolver over the real controller signature, so dropping the
 * attribute, or giving `approved` a default, fails here rather than in production.
 *
 * @internal
 */
final class AuthorizationApprovalControllerTest extends Unit
{
    private const string AUTHORIZATION_ID = 'a1b2c3';

    private const string LOCATION = 'https://oauth.tools/callback/code?code=abc';

    /** @var list<array{string, bool}> */
    private array $completed = [];

    public function _before(): void
    {
        $this->completed = [];
    }

    /**
     * Not one of these may reach the action as a denial: an access_denied redirect is
     * indistinguishable from one the user chose, so a client would be told the user said
     * no when the user was never asked.
     *
     * @dataProvider malformedBodyProvider
     */
    public function testMalformedBodyIsRefusedAndNeverReachesTheAction(string $body): void
    {
        $refused = null;

        try {
            $this->resolveApproval($body);
        } catch (Throwable $exception) {
            $refused = $exception;
        }

        $this->assertInstanceOf(HttpExceptionInterface::class, $refused);
        $this->assertGreaterThanOrEqual(400, $refused->getStatusCode());
        $this->assertLessThan(500, $refused->getStatusCode());
        $this->assertSame([], $this->completed);
    }

    /**
     * A body with no content type at all is refused too, rather than read as empty.
     */
    public function testBodyWithoutAContentTypeIsRefused(): void
    {
        $refused = null;

        try {
            $this->resolveApproval('{"approved":true}', contentType: null);
        } catch (Throwable $exception) {
            $refused = $exception;
        }

        $this->assertInstanceOf(HttpExceptionInterface::class, $refused);
        $this->assertSame([], $this->completed);
    }

    /**
     * @dataProvider decisionProvider
     */
    public function testWellFormedDecisionReachesTheService(string $body, bool $expected): void
    {
        $response = $this->invoke($body);

        $this->assertSame([[self::AUTHORIZATION_ID, $expected]], $this->completed);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['location' => self::LOCATION],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function malformedBodyProvider(): iterable
    {
        yield 'empty body' => [''];
        yield 'empty object' => ['{}'];
        yield 'null' => ['null'];
        yield 'wrong key' => ['{"approve":true}'];
        yield 'explicit null decision' => ['{"approved":null}'];
        yield 'unparseable json' => ['{"approved":'];
        yield 'not an object' => ['[true]'];
        yield 'non boolean decision' => ['{"approved":"maybe"}'];
        // The two that would go wrong in the dangerous direction: PHP reads either as
        // true, so a body that never said yes would be recorded as an approval.
        yield 'stringly false' => ['{"approved":"false"}'];
        yield 'numeric decision' => ['{"approved":1}'];
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public function decisionProvider(): iterable
    {
        yield 'approval' => ['{"approved":true}', true];
        yield 'denial' => ['{"approved":false}', false];
    }

    private function invoke(string $body): Response
    {
        $controller = $this->controller();

        return $controller(self::AUTHORIZATION_ID, $this->resolveApproval($body));
    }

    /**
     * Runs Symfony's payload mapping over the real controller argument, exactly as the
     * kernel does before calling the action.
     */
    private function resolveApproval(string $body, ?string $contentType = 'application/json'): ApproveAuthorization
    {
        $controller = $this->controller();
        $request = Request::create(
            '/pimcore-studio/api/oauth/authorizations/' . self::AUTHORIZATION_ID,
            'POST',
            content: $body,
        );
        $request->headers->remove('Content-Type');
        if ($contentType !== null) {
            $request->headers->set('Content-Type', $contentType);
        }

        $arguments = [self::AUTHORIZATION_ID, null];
        $metadata = (new ArgumentMetadataFactory())->createArgumentMetadata($controller);
        $resolver = new RequestPayloadValueResolver(
            $this->serializer(),
            Validation::createValidator(),
        );

        $resolved = [...$resolver->resolve($request, $metadata[1])];
        $this->assertNotSame(
            [],
            $resolved,
            'The approval argument is not mapped from the request payload.'
        );
        $arguments[1] = $resolved[0];

        $event = new ControllerArgumentsEvent(
            $this->makeEmpty(HttpKernelInterface::class),
            $controller,
            $arguments,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
        $resolver->onKernelControllerArguments($event);

        $mapped = $event->getArguments()[1];
        $this->assertInstanceOf(ApproveAuthorization::class, $mapped);

        return $mapped;
    }

    private function controller(): AuthorizationApprovalController
    {
        return new AuthorizationApprovalController(
            $this->serializer(),
            $this->makeEmpty(AuthorizationConsentServiceInterface::class, [
                'completeConsent' => function (string $id, bool $approved): AuthorizationRedirect {
                    $this->completed[] = [$id, $approved];

                    return new AuthorizationRedirect(self::LOCATION);
                },
            ]),
        );
    }

    /**
     * Built with a type extractor, like the framework's own `serializer` service, because
     * without one this test would pass while proving the opposite: a bare ObjectNormalizer
     * hands `"maybe"`, `"false"` and `1` straight to the constructor, PHP reads every one
     * of them as true, and a body that never said yes becomes an approval.
     */
    private function serializer(): SerializerInterface&DenormalizerInterface
    {
        return new Serializer(
            [new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())],
            [new JsonEncoder()],
        );
    }
}
