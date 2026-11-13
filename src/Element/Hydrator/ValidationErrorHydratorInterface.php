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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ValidationError;
use Pimcore\Model\Element\ValidationException;

/**
 * @internal
 */
interface ValidationErrorHydratorInterface
{
    /**
     * One entry per leaf error of the exception. The entered value of an error in a language
     * that is not in $viewableLanguages is left out of its parameters.
     *
     * @param list<string>|null $viewableLanguages null for no restriction
     *
     * @return list<ValidationError>
     */
    public function hydrate(ValidationException $exception, ?array $viewableLanguages = null): array;
}
