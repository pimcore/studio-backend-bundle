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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Version\CoauthorContextInterface;
use function is_string;
use function mb_strlen;
use function sprintf;

/**
 * Shared handling of the optional `coauthorType`/`coauthor` keys carried by element save payloads.
 *
 * @internal
 */
trait CoauthorContextTrait
{
    /**
     * Reads and validates the coauthor pair from a save payload. Extraction is deliberately kept
     * separate from running the save so that callers can validate before entering their
     * save try/catch, where an InvalidArgumentException would be masked as a save failure.
     *
     * @return array{type: string, coauthor: string}|null Null when the payload carries no complete pair
     *
     * @throws InvalidArgumentException
     */
    private function extractPayloadCoauthor(array $payload): ?array
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
     * Runs $callback with $coauthor applied to the version coauthor context, restoring the
     * previous context afterwards. A null $coauthor runs the callback unchanged.
     *
     * @param array{type: string, coauthor: string}|null $coauthor
     *
     * @param-immediately-invoked-callable $callback
     *
     * @param callable(): void $callback
     */
    private function runWithCoauthor(
        CoauthorContextInterface $coauthorContext,
        ?array $coauthor,
        callable $callback
    ): void {
        if ($coauthor === null) {
            $callback();

            return;
        }

        $coauthorContext->withCoauthor($coauthor['type'], $coauthor['coauthor'], $callback);
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
