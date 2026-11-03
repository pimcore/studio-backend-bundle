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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use ReflectionClass;
use function array_column;
use function str_contains;

/**
 * The label is resolved through core's resolver, so none of the chain below the title column is
 * asserted here - only the rung this endpoint ends on, which is null rather than the technical name.
 * The per-key KeyConfig load stays; what the group endpoint no longer issues is a label query per key.
 *
 * @internal
 */
final class KeyGroupLayoutServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int KEY_ID = 7;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const string LABEL = 'Gewicht';

    private const string COLUMN_TITLE = 'classificationstore.key.weight';

    private const string SUB_GROUP = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheLabelOfTheRequestingUsersLanguage(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            [self::KEY_ID => [self::LANGUAGE => self::LABEL]]
        );

        $this->assertSame(self::LABEL, $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());

        foreach ((new ReflectionClass(KeyGroupLayoutService::class))->getConstructor()->getParameters() as $parameter) {
            $this->assertFalse(str_contains((string)$parameter->getType(), 'Translator'));
        }
    }

    /**
     * An installation with an empty labels table is unchanged from the release before: the chain ends
     * at the key's title column.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheTitleColumnWithoutALabelRow(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE));

        $this->assertSame(self::COLUMN_TITLE, $keyLayout->getTitle());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenNoTitleIsStored(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME));

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * classificationstore_keys.title is NOT NULL DEFAULT '', so an empty title is the ordinary case
     * rather than an exotic one. It reaches the response as null, not as the empty string it was.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullForAnEmptyTitleColumn(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, ''));

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenTheKeyConfigurationCannotBeLoaded(): void
    {
        $keyLayout = $this->getKeyLayout(null);

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * A user whose language is unset is labelled in the installation default language rather than
     * against an empty one.
     *
     * @throws Exception
     */
    public function testAnEmptyUserLanguageFallsBackToTheDefaultLanguage(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME),
            [self::KEY_ID => [self::DEFAULT_LANGUAGE => self::LABEL]],
            ''
        );

        $this->assertSame(self::LABEL, $keyLayout->getTitle());
        $this->assertSame([self::DEFAULT_LANGUAGE], array_column($this->labelResolverCalls, 'language'));
    }

    /**
     * This endpoint builds its own KeyLayout rather than going through the group layout hydrator, so the
     * sub-group identifier reaches the response along a second, independent path. It is the raw identifier
     * of the assignment: this payload has no sub-group vocabulary to resolve it against, and nothing here
     * may substitute a name for it.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutCarriesTheRawSubGroupIdentifierOfTheRelation(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            subGroup: self::SUB_GROUP
        );

        $this->assertSame(self::SUB_GROUP, $keyLayout->getSubGroup());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNoSubGroupWhenTheRelationCarriesNone(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE));

        $this->assertNull($keyLayout->getSubGroup());
    }

    /**
     * @throws Exception
     */
    public function testPreloadKeyTitlesReadsAWholeGroupWithOneCall(): void
    {
        $keyIds = [11, 12, 13];

        $this->createService(null)->preloadKeyTitles($keyIds, self::LANGUAGE);

        $this->assertSame([$keyIds], $this->preloadedIds());
    }

    /**
     * @param array<int, array<string, string>> $labels
     *
     * @throws Exception
     */
    private function getKeyLayout(
        ?KeyConfig $keyConfig,
        array $labels = [],
        ?string $userLanguage = self::LANGUAGE,
        ?string $subGroup = null
    ): KeyLayout {
        return $this
            ->createService($keyConfig, $labels, $userLanguage, $subGroup)
            ->getKeyLayout(new LayoutParameter('classificationStore'), self::KEY_ID, self::GROUP_ID);
    }

    /**
     * @param array<int, array<string, string>> $labels
     *
     * @throws Exception
     */
    private function createService(
        ?KeyConfig $keyConfig,
        array $labels = [],
        ?string $userLanguage = self::LANGUAGE,
        ?string $subGroup = null
    ): KeyGroupLayoutService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAME);
        $relation->setDescription('The weight of the product');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');
        $relation->setSubGroup($subGroup);

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroupAndKey')
            ->with(self::GROUP_ID, self::KEY_ID)
            ->willReturn($relation);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver($labels),
            $this->createLabelLanguageResolver($userLanguage, self::DEFAULT_LANGUAGE)
        );
    }
}
