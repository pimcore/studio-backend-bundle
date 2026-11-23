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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Email\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\EmailLogResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\MappedParameter\EmailLogParameters;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepository;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Model\Tool\Email\Log\Listing;
use ReflectionMethod;

final class EmailLogRepositoryTest extends Unit
{
    public function testAppliesDocumentIdCondition(): void
    {
        [$condition, $conditionVariables] = $this->getAppliedCondition(new EmailLogParameters(documentId: 42));

        $this->assertSame('documentId = ?', $condition);
        $this->assertSame([42], $conditionVariables);
    }

    public function testAppliesNoConditionWithoutDocumentId(): void
    {
        [$condition, $conditionVariables] = $this->getAppliedCondition(new EmailLogParameters());

        $this->assertNull($condition);
        $this->assertNull($conditionVariables);
    }

    /**
     * @return array{0: string|null, 1: array|null}
     */
    private function getAppliedCondition(EmailLogParameters $parameters): array
    {
        $repository = new EmailLogRepository($this->makeEmpty(EmailLogResolverInterface::class));
        $condition = null;
        $conditionVariables = null;
        $listing = null;
        $listing = $this->makeEmpty(Listing::class, [
            'setCondition' => static function (
                string $appliedCondition,
                float|array|bool|int|string|null $appliedVariables = null
            ) use (&$listing, &$condition, &$conditionVariables): Listing {
                $condition = $appliedCondition;
                $conditionVariables = $appliedVariables;

                return $listing;
            },
        ]);
        $applyDocumentFilter = new ReflectionMethod(EmailLogRepository::class, 'applyDocumentFilter');
        $applyDocumentFilter->invoke($repository, $listing, $parameters);

        return [$condition, $conditionVariables];
    }

    public function testUsesAllowedSortKey(): void
    {
        $this->assertSame('subject', $this->getAppliedOrderKey(new SortFilter('subject', 'ASC')));
    }

    public function testFallsBackToDefaultOrderForUnknownSortKey(): void
    {
        $this->assertSame('sentDate', $this->getAppliedOrderKey(new SortFilter('subject DESC, id', 'ASC')));
    }

    private function getAppliedOrderKey(SortFilter $sortFilter): string
    {
        $repository = new EmailLogRepository($this->makeEmpty(EmailLogResolverInterface::class));
        $orderKey = null;
        $listing = null;
        $listing = $this->makeEmpty(Listing::class, [
            'setOrderKey' => static function (array|string $key) use (&$listing, &$orderKey): Listing {
                $orderKey = $key;

                return $listing;
            },
        ]);
        $applySearchOptions = new ReflectionMethod($repository, 'applySearchOptions');
        $applySearchOptions->invoke($repository, $listing, new FilterParameter(sortFilter: $sortFilter));

        $this->assertIsString($orderKey);

        return $orderKey;
    }
}
