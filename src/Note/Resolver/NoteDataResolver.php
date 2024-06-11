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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Resolver;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\NoteUser;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Note as CoreNote;

/**
 * @internal
 */
final class NoteDataResolver implements NoteDataResolverInterface
{
    /** @var array<int, string>  */
    private array $elementCache = [];

    /** @var array<int, NoteUser>  */
    private array $userCache = [];

    public function __construct(
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly UserResolverInterface $userResolver
    )
    {
    }

    public function extractCPath(CoreNote $note) : string
    {
        if (isset($this->elementCache[$note->getCid()])) {
            return $this->elementCache[$note->getCid()];
        }

        if (!$note->getCid() || !$note->getCtype()) {
            return '';
        }

        $element = $this->serviceResolver->getElementById($note->getCtype(), $note->getCid());

        if (!$element) {
            return '';
        }

        $this->elementCache[$note->getCid()] = $element->getFullPath();

        return $this->elementCache[$note->getCid()];
    }

    public function resolveUserData(CoreNote $note) : NoteUser
    {
        if (!$note->getUser()) {
            return new NoteUser();
        }

        if (isset($this->userCache[$note->getUser()])) {
            return $this->userCache[$note->getUser()];
        }

        $user = $this->userResolver->getById($note->getUser());

        if (!$user) {
            return new NoteUser();
        }

        $this->userCache[$note->getUser()] =  new NoteUser(
            $user->getId(),
            $user->getName(),
        );

       return $this->userCache[$note->getUser()];
    }

    public function resolveData(CoreNote $note): array
    {
        // prepare key-values
        $keyValues = [];
        foreach ($note->getData() as $name => $d) {

            $type = $d['type'];

            $data = match($type) {
                'document', 'object', 'asset' => $this->resolveElementData($d['data']),
                'date' => is_object($d['data']) ? $d['data']->getTimestamp() : $d['data'],
                default => $d['data'],
            };

            $keyValue = [
                'type' => $type,
                'name' => $name,
                'data' => $data,
            ];

            $keyValues[] = $keyValue;
        }

        return $keyValues;
    }

    private function resolveElementData(?ElementInterface $element): array
    {
        if (!$element) {
            return [];
        }

        return [
            'id' => $element->getId(),
            'path' => $element->getRealFullPath(),
            'type' => $element->getType(),
        ];
    }
}
