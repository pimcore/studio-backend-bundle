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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

/**
 * @internal
 */
final class DataService implements DataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly TextEncoderInterface $textEncoder,
    )
    {
    }


    public function getUTF8EncodedData(int $id): string
    {
        $element = $this->getElement($this->serviceResolver, 'asset', $id);

        return $this->textEncoder->encodeUTF8($element);
    }
}