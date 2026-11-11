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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Note\Resolver;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Resolver\NoteDataResolver;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\Element\Note;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final class NoteDataResolverTest extends Unit
{
    public function testNoteOfAServiceUserCarriesItsIdAndName(): void
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(7);
        $serviceUser->setName('svc-erp-sync');

        $noteUser = $this->resolver([7 => $serviceUser])->resolveUserData($this->note(7));

        $this->assertSame(7, $noteUser->getId());
        $this->assertSame('svc-erp-sync', $noteUser->getName());
    }

    public function testNoteOfAnUnknownUserCarriesNoUser(): void
    {
        $noteUser = $this->resolver([])->resolveUserData($this->note(99));

        $this->assertNull($noteUser->getId());
        $this->assertNull($noteUser->getName());
    }

    /**
     * @param array<int, User> $users
     */
    private function resolver(array $users): NoteDataResolver
    {
        return new NoteDataResolver(
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(UserAccountResolverInterface::class, [
                'getAccountById' => static fn (int $id): ?User => $users[$id] ?? null,
            ])
        );
    }

    private function note(int $userId): Note
    {
        $note = new Note();
        $note->setUser($userId);

        return $note;
    }
}
