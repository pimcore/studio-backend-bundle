<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use function count;
use function in_array;
use function is_array;
use function is_null;

/**
 * Copied from old admin-ui-classic-bundle
 * https://github.com/pimcore/admin-ui-classic-bundle/blob/e71ee902ab1274a64d6b80d56af28f1855944dfd/src/Controller/Admin/DataObject/DataObjectController.php#L597
 * Use with caution, this is a copy from the admin-ui-classic-bundle
 *
 * @internal
 */
final readonly class ApplyChangesHelper implements ApplyChangesHelperInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private DataObjectResolverInterface $dataObjectResolver,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function applyChanges(Concrete $object, array $changes): void
    {
        foreach ($changes as $key => $value) {
            try {
                $fd = $object->getClass()->getFieldDefinition($key);
            } catch (Exception) {
                throw new NotFoundException('Class ', $object->getClassId());
            }

            if ($fd) {
                if ($fd instanceof Localizedfields) {
                    /** @var User $user */
                    $user = $this->securityService->getCurrentUser();
                    if (!$user->getAdmin()) {
                        $allowedLanguages = $this->dataObjectServiceResolver->getLanguagePermissions(
                            $object,
                            $user,
                            'lEdit'
                        );
                        if (!is_null($allowedLanguages)) {
                            $allowedLanguages = array_keys($allowedLanguages);
                            $submittedLanguages = array_keys($changes[$key]);
                            foreach ($submittedLanguages as $submittedLanguage) {
                                if (!in_array($submittedLanguage, $allowedLanguages)) {
                                    unset($value[$submittedLanguage]);
                                }
                            }
                        }
                    }
                }

                if ($fd instanceof ReverseObjectRelation) {
                    try {
                        $remoteClass = $this->classDefinitionResolver->getByName($fd->getOwnerClassName());
                    } catch (Exception) {
                        throw new NotFoundException('Class definition ', $fd->getOwnerClassName());
                    }

                    $relations = $object->getRelationData($fd->getOwnerFieldName(), false, $remoteClass->getId());
                    $toAdd = $this->detectAddedRemoteOwnerRelations($relations, $value);
                    $toDelete = $this->detectDeletedRemoteOwnerRelations($relations, $value);
                    if (count($toAdd) > 0 || count($toDelete) > 0) {
                        try {
                            $this->processRemoteOwnerRelations($object, $toDelete, $toAdd, $fd->getOwnerFieldName());
                        } catch (DuplicateFullPathException $e) {
                            throw new DatabaseException($e->getMessage());
                        }
                    }
                } else {
                    $object->setValue($key, $fd->getDataFromEditmode($value, $object));
                }
            }
        }
    }

    private function detectAddedRemoteOwnerRelations(array $relations, array $value): array
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }

        foreach ($value as $row) {
            $changed[] = $row['id'];
        }

        return array_diff($changed, $originals);
    }

    private function detectDeletedRemoteOwnerRelations(array $relations, array $value): array
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }

        foreach ($value as $row) {
            $changed[] = $row['id'];
        }

        return array_diff($originals, $changed);
    }

    /**
     * @throws DuplicateFullPathException
     */
    private function processRemoteOwnerRelations(
        Concrete $object,
        array $toDelete,
        array $toAdd,
        string $ownerFieldName
    ): void {
        $getter = 'get' . ucfirst($ownerFieldName);
        $setter = 'set' . ucfirst($ownerFieldName);

        foreach ($toDelete as $id) {
            $owner = $this->dataObjectResolver->getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    for ($i = 0; $i < count($currentData); $i++) {
                        if ($currentData[$i]->getId() == $object->getId()) {
                            unset($currentData[$i]);
                            $owner->$setter($currentData);

                            break;
                        }
                    }
                } else {
                    if ($currentData->getId() == $object->getId()) {
                        $owner->$setter(null);
                    }
                }
            }
            $owner->setUserModification($this->securityService->getCurrentUser()->getId());
            $owner->save();
            $this->logger->debug(
                'Saved object id [ ' . $owner->getId() . ' ] by remote modification through
                [' . $object->getId() . '], Action: deleted [ ' . $object->getId() . " ]
                from [ $ownerFieldName]"
            );
        }

        foreach ($toAdd as $id) {
            $owner = $this->dataObjectResolver->getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    $currentData[] = $object;
                } else {
                    $currentData = $object;
                }
                $owner->$setter($currentData);
                $owner->setUserModification($this->securityService->getCurrentUser()->getId());
                $owner->save();
                $this->logger->debug(
                    'Saved object id [ ' . $owner->getId() . ' ] by remote modification
                    through [' . $object->getId() . '], Action:
                    added [ ' . $object->getId() . " ] to [ $ownerFieldName ]"
                );
            }
        }
    }
}
