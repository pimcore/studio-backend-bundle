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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Exception;

use InvalidArgumentException;

/**
 * An MCP tool argument the caller got wrong, described in terms of that argument.
 *
 * This is the one exception type {@see McpToolErrorHandler} forwards to the client verbatim, so
 * throwing it is a statement by the thrower:
 *
 * > I composed this message for the caller, out of the caller's own input, and it quotes nothing
 * > from a lower layer.
 *
 * Never throw it wrapping another exception's message. `Failed to save element 5: <inner>` reads
 * like an argument error while carrying whatever DBAL, OpenSearch or Flysystem put in the inner
 * message, and the handler has no way to tell the difference.
 *
 * {@see ObjectParameterNormalizer} is the shipped example of a correct throw: the message names the
 * parameter and says how to send nothing.
 *
 * It extends SPL `InvalidArgumentException` so that tools which already type-catch that class ahead
 * of their terminal `catch (Throwable)` keep matching without edits.
 */
final class InvalidMcpToolArgumentException extends InvalidArgumentException
{
}
