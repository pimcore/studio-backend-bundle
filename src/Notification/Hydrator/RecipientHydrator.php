<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */


namespace Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Recipient;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class RecipientHydrator implements RecipientHydratorInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function hydrate(UserInterface|UserRoleInterface $user): Recipient
    {
        $recipientName = $user->getName();
        if ($user->getType() === 'role') {
            $group = $this->translator->trans('group', [], 'admin');
            $recipientName = $group . ' - ' . $recipientName;
        }

        return new Recipient(
            id: $user->getId(),
            recipientName: $recipientName
        );
    }
}