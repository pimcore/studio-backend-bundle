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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\DataObject\Consent\Service;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Consent;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ConsentAdapter implements SetterDataInterface
{
    public function __construct(
        private Service $service
    ) {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): Consent {
        $value = $data[$key] ?? null;
        $noteId = null;

        if ($value === 'false') {
            $value = false;
        }

        /** @var Consent $oldData */
        $oldData = $element->get($key);

        if (!$oldData || $oldData->getConsent() !== $value) {
            if ($value) {
                $note = $this->service->insertConsentNote(
                    $element,
                    $key,
                    'Manually by User via Pimcore Backend.'
                );
            } else {
                $note = $this->service->insertRevokeNote($element, $key);
            }
            $noteId = $note->getId();
        } elseif ($oldData instanceof Consent) {
            $noteId = $oldData->getNoteId();
        }

        return new Consent($value, $noteId);
    }
}
