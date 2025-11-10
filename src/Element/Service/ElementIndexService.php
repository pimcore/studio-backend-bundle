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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Listing;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use function sprintf;

/**
 * @internal
 */
final readonly class ElementIndexService implements ElementIndexServiceInterface
{
    private const string DATA_OBJET_TABLE = 'objects';

    public function __construct(
        private ConcreteObjectResolverInterface $concreteResolver,
        private DbResolverInterface $dbResolver,
        private LoggerInterface $logger,
    ) {
    }

    public function indexRelatedElements(ElementInterface $element, int $newIndex): void
    {
        if ($element instanceof DataObject) {
            $this->indexRelatedObjects($element, $newIndex);

            return;
        }

        if ($element instanceof Document) {
            $this->indexRelatedDocuments($element, $newIndex);
        }
    }

    private function indexRelatedObjects(DataObject $updatedObject, int $newIndex): void
    {
        $parent = $updatedObject->getParent();
        if ($parent === null) {
            return;
        }

        $this->executeInsideTransaction(
            fn () => $this->reindexSiblingObjects($updatedObject, $newIndex, $parent)
        );
    }

    private function indexRelatedDocuments(Document $updatedDocument, int $newIndex): void
    {
        $parentId = $updatedDocument->getParentId();
        if ($parentId === null) {
            return;
        }

        // if changed the index change also all documents on the same level
        $updatedDocument->saveIndex($newIndex);

        $list = new Listing();
        $list->setCondition('parentId = ? AND id != ?', [$parentId, $updatedDocument->getId()]);
        $list->setOrderKey('index');
        $list->setOrder('asc');
        $childrenList = $list->load();

        $count = 0;
        foreach ($childrenList as $child) {
            if ($count === $newIndex) {
                $count++;
            }
            $child->saveIndex($count);
            if ($child instanceof PageSnippet) {
                $this->updateLatestVersionIndex($child, $count);
            }
            $count++;
        }
    }

    /**
     * @throws DbalException
     */
    private function reindexSiblingObjects(
        DataObject $updatedObject,
        int $newIndex,
        AbstractObject $parent
    ): void {
        $updatedObject->saveIndex($newIndex);

        $this->updateSiblingIndexes($updatedObject, $newIndex, $parent);
        $this->updateSiblingVersions($updatedObject, $newIndex);
    }

    /**
     * @throws DbalException
     */
    private function updateSiblingIndexes(
        DataObject $updatedObject,
        int $newIndex,
        AbstractObject $parent
    ): void {
        $db = $this->dbResolver->get();
        $db->executeStatement(
            'UPDATE '. self::DATA_OBJET_TABLE .' o,
            (
                SELECT newIndex, id
                FROM (
                    With cte As (SELECT `index`, id FROM ' . self::DATA_OBJET_TABLE .
            ' WHERE parentId = ? AND id != ? ORDER BY `index` LIMIT '.
            $parent->getChildAmount() .')
                    SELECT @n := IF(@n = ? - 1,@n + 2,@n + 1) AS newIndex, id
                    FROM cte,
                    (SELECT @n := -1) variable
                ) tmp
            ) order_table
            SET o.index = order_table.newIndex
            WHERE o.id=order_table.id',
            [
                $updatedObject->getParentId(),
                $updatedObject->getId(),
                $newIndex,
            ]
        );
    }

    /**
     * @throws Exception|DbalException
     * @throws Exception
     */
    private function updateSiblingVersions(
        DataObject $updatedObject,
        int $newIndex
    ): void {
        $db = $this->dbResolver->get();
        $siblings = $db->fetchAllAssociative(
            'SELECT id, modificationDate, versionCount, `key`, `index` FROM ' . self::DATA_OBJET_TABLE .
            ' WHERE parentId = ? AND id != ? ORDER BY `index` ASC',
            [$updatedObject->getParentId(), $updatedObject->getId()]
        );

        $index = 0;
        foreach ($siblings as $sibling) {
            if ($index === $newIndex) {
                $index++;
            }

            $element = $this->concreteResolver->getById($sibling['id']);
            if ($element === null) {
                continue;
            }

            $this->updateLatestVersionIndex($element, $index);
            $index++;

            $element->clearDependentCache();
        }
    }

    private function updateLatestVersionIndex(Concrete|PageSnippet $element, int $newIndex): void
    {
        $latestVersion = $element->getLatestVersion();
        if ($latestVersion === null) {
            return;
        }

        // don't renew references (which means loading the target elements)
        // Not needed as we just save a new version with the updated index
        $version = $latestVersion->loadData(false);
        if ($newIndex !== $version->getIndex()) {
            $version->setIndex($newIndex);
        }

        $latestVersion->save();
    }

    private function executeInsideTransaction(callable $fn): void
    {
        $db = $this->dbResolver->get();
        $maxRetries = 5;
        for ($retries = 0; $retries < $maxRetries; $retries++) {
            try {
                $db->beginTransaction();

                $fn();

                $db->commit();

                break;
            } catch (Exception $e) {
                $this->rollBackTransaction($db, $retries, $e);
            } catch (DbalException $e) {
                throw new DatabaseException('Database error occurred: ' . $e->getMessage(), previous: $e);
            }
        }
    }

    private function rollBackTransaction(Connection $db, int $retries, Exception $exception): void
    {
        $maxRetries = 5;

        try {
            $db->rollBack();

            // we try to start the transaction $maxRetries times again (deadlocks, ...)
            if ($retries < ($maxRetries - 1)) {
                $run = $retries + 1;
                $waitTime = random_int(1, 5) * 100000; // microseconds
                $this->logger->warning(
                    'Unable to finish transaction (' . $run . ". run) because of the following reason '" .
                    $exception->getMessage() . "'. --> Retrying in " . $waitTime .
                    ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')'
                );

                usleep($waitTime); // wait a specified time until we restart the transaction
            } else {
                // if the transaction still fails after $maxRetries retries, we throw out the exception
                $this->logger->error(
                    'Finally giving up restarting the same transaction again and again, last message: ' .
                    $exception->getMessage()
                );

                throw new DatabaseException(
                    message: sprintf('Error while updating indexes: %s', $exception->getMessage()),
                    previous: $exception
                );
            }

        } catch (DbalException $e) {
            throw new DatabaseException('Database error occurred: ' . $e->getMessage(), previous: $e);
        } catch (RandomException $e) {
            throw new EnvironmentException($e->getMessage(), previous: $e);
        }
    }
}
