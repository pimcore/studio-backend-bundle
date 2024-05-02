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

namespace Pimcore\Bundle\StudioBackendBundle\Property;

use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertiesListing;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class Repository implements RepositoryInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function listProperties(PropertiesParameters $parameters): PropertiesListing
    {
        $list = new PropertiesListing();
        $type = $parameters->getElementType();
        $query = $parameters->getQuery();
        $translator = $this->translator;
        $list->setFilter(static function (Predefined $predefined) use ($type, $query, $translator) {
            if (!str_contains($predefined->getCtype(), $type)) {
                return false;
            }
            if ($query && stripos($translator->trans($predefined->getName(), [], 'admin'), $query) === false) {
                return false;
            }

            return true;
        });

        return $list;
    }
}