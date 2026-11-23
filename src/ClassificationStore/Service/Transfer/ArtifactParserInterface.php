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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
interface ArtifactParserInterface
{
    /**
     * What joins the two endpoints of a relation into the one key its section is held by. A byte no
     * identifier may carry, so that two identifiers can never be read as one pair.
     */
    public const string ENTRY_KEY_SEPARATOR = "\0";

    /**
     * Reads the artifact and refuses, as one unprocessable payload, anything that is not parseable
     * JSON or does not carry the shape an export writes.
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $json): ArtifactDocument;

    /**
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    public function readKeyEntry(array $entry): ArtifactKeyEntry;

    /**
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    public function readAssignmentEntry(array $entry): ArtifactAssignmentEntry;

    /**
     * The sorter of a relation entry, zero when the entry leaves it out.
     *
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    public function readSorter(array $entry, ArtifactSection $section): int;
}
