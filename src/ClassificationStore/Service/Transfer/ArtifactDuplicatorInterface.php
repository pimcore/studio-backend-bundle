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

/**
 * The document a file describing another store is applied as: the same configuration under
 * identifiers that are this installation's to hand out.
 *
 * A member identifier is unique per store ({@see \Pimcore\Model\DataObject\Classificationstore} and
 * the `storeId_identifier` indexes), so two stores sharing one identifier string are two unrelated
 * rows regardless - a delete, an assignment and a search all key off the row id and the store they
 * sit in. Reusing the file's strings would therefore link nothing and only suggest, to a UI that
 * shows the identifier, that the two rows are one.
 *
 * @internal
 */
interface ArtifactDuplicatorInterface
{
    /**
     * The document with a freshly generated identifier for every sub group, collection, group and
     * key, and with both relation sections rewritten onto those identifiers.
     *
     * A reference naming an entity the document does not carry is left as it stands, so that the
     * apply refuses it for the endpoint it has always been missing rather than for one this rewrite
     * renamed away.
     *
     * The store entry is handed through untouched: the target keeps its own identity and name, and
     * nothing reads the entry in this mode ({@see ReconcilerInterface::apply()}).
     */
    public function duplicate(ArtifactDocument $document): ArtifactDocument;
}
