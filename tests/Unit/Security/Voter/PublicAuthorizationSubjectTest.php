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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\Voter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Controller\ThumbnailController;
use Pimcore\Bundle\StudioBackendBundle\Translation\Controller\TranslationController;
use Pimcore\Bundle\StudioBackendBundle\User\Controller\ResetPasswordController;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\EventListener\IsGrantedAttributeListener;

/**
 * @internal
 */
final class PublicAuthorizationSubjectTest extends Unit
{
    /**
     * The public voter tells the endpoints apart by name, so the subject must not depend on how Symfony
     * resolves the controller arguments, e.g. on an argument that is not mapped from the request yet.
     */
    public function testPublicEndpointsPassTheirNameAsSubject(): void
    {
        $endpoints = [
            [TranslationController::class, 'getTranslations', [new MapRequestPayload()], 'translation'],
            [ResetPasswordController::class, 'resetPassword', [new MapRequestPayload()], 'resetPassword'],
            [ThumbnailController::class, 'settingsAdminThumbnail', ['settingsAdminThumbnail'], 'settingsAdminThumbnail'],
        ];

        foreach ($endpoints as [$class, $method, $arguments, $expectedSubject]) {
            $authorizationChecker = new class() implements AuthorizationCheckerInterface {
                public mixed $subject = null;

                public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
                {
                    $this->subject = $subject;

                    return true;
                }
            };

            $event = new ControllerArgumentsEvent(
                $this->createStub(HttpKernelInterface::class),
                [(new ReflectionClass($class))->newInstanceWithoutConstructor(), $method],
                $arguments,
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            );

            (new IsGrantedAttributeListener($authorizationChecker))->onKernelControllerArguments($event);

            $this->assertSame($expectedSubject, $authorizationChecker->subject, $class);
        }
    }
}
