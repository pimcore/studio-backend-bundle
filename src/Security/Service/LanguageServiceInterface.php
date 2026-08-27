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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface LanguageServiceInterface
{
    /**
     * @return array<int, string>
     *
     * @throws InvalidArgumentException
     */
    public function getUserAllowedLanguages(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array;

    /**
     * Tells whether the user may use the language independent ("default") value of a localized
     * field for the given language permission. It is granted unless the configured language list
     * explicitly leaves it out.
     *
     * @throws InvalidArgumentException
     */
    public function isLanguageIndependentValueAllowed(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): bool;

    /**
     * @throws ForbiddenException
     */
    public function validateAdminPermission(UserInterface $user, string $domain): void;

    public function getTranslationAllowedLanguages(UserInterface $user, string $domain): array;
}
