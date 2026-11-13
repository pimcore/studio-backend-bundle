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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContextInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Version\CoauthorContextInterface;
use function is_string;
use function mb_strlen;
use function sprintf;
use function trigger_deprecation;

/**
 * @internal
 */
final readonly class CoauthorService implements CoauthorServiceInterface
{
    public function __construct(
        private CoauthorContextInterface $coauthorContext,
        private McpCallContextInterface $mcpCallContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function extractFromPayload(array $payload): ?array
    {
        // Inside an MCP tool call the coauthor is set by the MCP server only (or there is none): a value
        // claimed in the payload is dropped, whether or not a server-side coauthor is active.
        if ($this->mcpCallContext->isInMcpCall()) {
            return null;
        }

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

        trigger_deprecation(
            'pimcore/studio-backend-bundle',
            '2026.4',
            'Sending "%s" and "%s" in an element patch or update payload is deprecated and will be removed in ' .
            '2027.1 (https://github.com/pimcore/platform-version/issues/814). The version coauthor is set by the ' .
            'server: MCP servers stamp it themselves.',
            ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE,
            ElementSaveServiceInterface::INDEX_COAUTHOR
        );

        return ['type' => $coauthorType, 'coauthor' => $coauthor];
    }

    /**
     * {@inheritdoc}
     */
    public function withoutPayloadCoauthorInMcpCall(array $payload): array
    {
        if (!$this->mcpCallContext->isInMcpCall()) {
            return $payload;
        }

        unset(
            $payload[ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE],
            $payload[ElementSaveServiceInterface::INDEX_COAUTHOR]
        );

        return $payload;
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
