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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Note\Schema\NoteUser;
use Pimcore\Model\Element\Note as CoreNote;

/**
 * @internal
 */
interface NoteDataResolverInterface
{
    public function resolveUserData(CoreNote $note): NoteUser;

    public function extractCPath(CoreNote $note): string;

    public function resolveNoteData(CoreNote $note): array;
}
