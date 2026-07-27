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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Version\CoauthorContextInterface;
use function is_string;
use function mb_strlen;
use function sprintf;

/**
 * @internal
 */
final readonly class CoauthorService implements CoauthorServiceInterface
{
    public function __construct(
        private CoauthorContextInterface $coauthorContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function extractFromPayload(array $payload): ?array
    {
        $coauthorType = $payload[ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE] ?? null;
        $coauthor = $payload[ElementSaveServiceInterface::INDEX_COAUTHOR] ?? null;

        if (!is_string($coauthorType) || $coauthorType === '' || !is_string($coauthor) || $coauthor === '') {
            return null;
        }

        $this->validateLength(
            ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE,
            $coauthorType,
            VersionCoauthor::MAX_TYPE_LENGTH
        );
        $this->validateLength(
            ElementSaveServiceInterface::INDEX_COAUTHOR,
            $coauthor,
            VersionCoauthor::MAX_COAUTHOR_LENGTH
        );

        return ['type' => $coauthorType, 'coauthor' => $coauthor];
    }

    /**
     * {@inheritdoc}
     */
    public function runWithCoauthor(?array $coauthor, callable $callback): void
    {
        if ($coauthor === null) {
            $callback();

            return;
        }

        $this->coauthorContext->withCoauthor($coauthor['type'], $coauthor['coauthor'], $callback);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateLength(string $key, string $value, int $maxLength): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(
                sprintf('%s must not exceed %d characters', $key, $maxLength)
            );
        }
    }
}
