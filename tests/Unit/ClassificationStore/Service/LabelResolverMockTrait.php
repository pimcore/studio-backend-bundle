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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\LabelLanguageResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\LabelLanguageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Pimcore\Model\UserInterface;
use function array_values;

/**
 * A recording stand-in for core's label resolver. The language chain itself is core's to prove
 * (LabelResolverTest); what is mirrored here is only the rung the two accessors end on - the key's
 * own title column, and then the technical name for getTitle() against null for getStoredTitle().
 *
 * @internal
 */
trait LabelResolverMockTrait
{
    /**
     * @var list<array{method: string, ids: list<int>, language: string}>
     */
    private array $labelResolverCalls = [];

    /**
     * @param array<int, array<string, string>> $labels key id => language => stored label
     */
    private function createLabelResolver(array $labels = []): LabelResolverInterface
    {
        $resolver = $this->createMock(LabelResolverInterface::class);

        $resolver
            ->method('preload')
            ->willReturnCallback(
                function (LabelEntityType $entityType, array $entityIds, string $language): void {
                    $this->labelResolverCalls[] = [
                        'method' => 'preload',
                        'ids' => array_values($entityIds),
                        'language' => $language,
                    ];
                }
            );

        $resolver
            ->method('getStoredTitle')
            ->willReturnCallback(
                fn (KeyConfig $keyConfig, string $language): ?string => $this->resolveStoredTitle(
                    'getStoredTitle',
                    $labels,
                    $keyConfig,
                    $language
                )
            );

        $resolver
            ->method('getTitle')
            ->willReturnCallback(
                fn (KeyConfig $keyConfig, string $language): ?string => $this->resolveStoredTitle(
                    'getTitle',
                    $labels,
                    $keyConfig,
                    $language
                ) ?? $keyConfig->getName()
            );

        return $resolver;
    }

    private function createKeyConfig(int $keyId, string $name, ?string $title = null): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId($keyId);
        $keyConfig->setName($name);

        if ($title !== null) {
            $keyConfig->setTitle($title);
        }

        return $keyConfig;
    }

    /**
     * @param string|null $userLanguage the language of the current user, null to have no current
     *                                  user resolvable at all
     */
    private function createLabelLanguageResolver(
        ?string $userLanguage,
        ?string $defaultLanguage
    ): LabelLanguageResolverInterface {
        $securityService = $this->createMock(SecurityServiceInterface::class);

        if ($userLanguage === null) {
            $securityService
                ->method('getCurrentUser')
                ->willThrowException(new UserNotFoundException());
        } else {
            $user = $this->createMock(UserInterface::class);
            $user
                ->method('getLanguage')
                ->willReturn($userLanguage);

            $securityService
                ->method('getCurrentUser')
                ->willReturn($user);
        }

        $toolResolver = $this->createMock(ToolResolverInterface::class);
        $toolResolver
            ->method('getDefaultLanguage')
            ->willReturn($defaultLanguage);

        return new LabelLanguageResolver($securityService, $toolResolver);
    }

    /**
     * @return list<list<int>> the id list of every preload() call, in call order
     */
    private function preloadedIds(): array
    {
        $ids = [];

        foreach ($this->labelResolverCalls as $call) {
            if ($call['method'] === 'preload') {
                $ids[] = $call['ids'];
            }
        }

        return $ids;
    }

    /**
     * @return list<string> the method name of every recorded call, in call order
     */
    private function calledMethods(): array
    {
        $methods = [];

        foreach ($this->labelResolverCalls as $call) {
            $methods[] = $call['method'];
        }

        return $methods;
    }

    /**
     * @param array<int, array<string, string>> $labels
     */
    private function resolveStoredTitle(
        string $method,
        array $labels,
        KeyConfig $keyConfig,
        string $language
    ): ?string {
        $keyId = $keyConfig->getId() ?? 0;

        $this->labelResolverCalls[] = ['method' => $method, 'ids' => [$keyId], 'language' => $language];

        $stored = $labels[$keyId][$language] ?? null;
        if ($stored !== null) {
            return $stored;
        }

        $ownTitle = $keyConfig->getTitle();

        return $ownTitle === '' ? null : $ownTitle;
    }
}
