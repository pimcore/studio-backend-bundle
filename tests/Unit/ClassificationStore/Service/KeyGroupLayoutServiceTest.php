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

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Pimcore\Model\UserInterface;
use ReflectionClass;
use function range;
use function str_contains;

/**
 * @internal
 */
final class KeyGroupLayoutServiceTest extends Unit
{
    private const int KEY_ID = 7;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string USER_LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const string RESOLVED_LABEL = 'Gehäuse';

    /**
     * The title column of the key configuration, which the service does not read any more.
     */
    private const string COLUMN_TITLE = 'Column title';

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheResolvedLabel(): void
    {
        $keyLayout = $this->getKeyLayout(self::RESOLVED_LABEL);

        $this->assertSame(self::RESOLVED_LABEL, $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());

        foreach ((new ReflectionClass(KeyGroupLayoutService::class))->getConstructor()->getParameters() as $parameter) {
            $this->assertFalse(str_contains((string)$parameter->getType(), 'Translator'));
        }
    }

    /**
     * The nullable accessor is the one this path reads, so the configuration UI can still tell
     * "no title set" apart from "title equals name".
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenNoLabelIsResolved(): void
    {
        $keyLayout = $this->getKeyLayout(null);

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsAnEmptyLabelVerbatim(): void
    {
        $keyLayout = $this->getKeyLayout('');

        $this->assertSame('', $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * An ETIM-sized group is preloaded with one call, so the loop that follows costs no query per key.
     */
    public function testThePreloadReadsTheLabelsOfEveryGivenKeyWithOneCall(): void
    {
        $keyIds = range(1, 121);

        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->expects($this->once())
            ->method('preload')
            ->with(LabelEntityType::Key, $keyIds, self::USER_LANGUAGE);

        $this->createService($labelResolver, $this->createSecurityService())->preloadKeyTitles($keyIds);
    }

    /**
     * Without a resolvable user there is no language to ask for, so the installation's default one is
     * what the resolver walks from.
     *
     * @throws Exception
     */
    public function testTheLabelIsReadForTheDefaultLanguageWithoutAUser(): void
    {
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService
            ->method('getCurrentUser')
            ->willThrowException(new UserNotFoundException());

        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->expects($this->once())
            ->method('getStoredTitle')
            ->with($this->anything(), self::DEFAULT_LANGUAGE)
            ->willReturn(self::RESOLVED_LABEL);

        $title = $this->createService($labelResolver, $securityService)->getKeyTitle(self::KEY_ID);

        $this->assertSame(self::RESOLVED_LABEL, $title);
    }

    /**
     * @throws Exception
     */
    private function getKeyLayout(?string $resolvedLabel): KeyLayout
    {
        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->method('getStoredTitle')
            ->willReturnCallback(
                function (KeyConfig $keyConfig, string $language) use ($resolvedLabel): ?string {
                    $this->assertSame(self::KEY_ID, $keyConfig->getId());
                    $this->assertSame(self::USER_LANGUAGE, $language);

                    return $resolvedLabel;
                }
            );

        $service = $this->createService($labelResolver, $this->createSecurityService());

        return $service->getKeyLayout(new LayoutParameter('classificationStore'), self::KEY_ID, self::GROUP_ID);
    }

    private function createService(
        LabelResolverInterface $labelResolver,
        SecurityServiceInterface $securityService
    ): KeyGroupLayoutService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAME);
        $relation->setDescription('The weight of the product');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroupAndKey')
            ->with(self::GROUP_ID, self::KEY_ID)
            ->willReturn($relation);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        $toolResolver = $this->createMock(ToolResolverInterface::class);
        $toolResolver
            ->method('getDefaultLanguage')
            ->willReturn(self::DEFAULT_LANGUAGE);

        return new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $this->createKeyConfigResolver(),
            $labelResolver,
            $securityService,
            $toolResolver,
        );
    }

    /**
     * The key configuration carries a title of its own; it is the resolver's last rung, not the
     * service's, so a disagreeing column never reaches the response directly.
     */
    private function createKeyConfigResolver(): KeyConfigResolverInterface
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        $keyConfig->setTitle(self::COLUMN_TITLE);

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return $keyConfigResolver;
    }

    private function createSecurityService(): SecurityServiceInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->method('getLanguage')
            ->willReturn(self::USER_LANGUAGE);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService
            ->method('getCurrentUser')
            ->willReturn($user);

        return $securityService;
    }
}
