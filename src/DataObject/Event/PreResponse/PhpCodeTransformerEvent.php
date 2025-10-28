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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\PhpCodeTransformer;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class PhpCodeTransformerEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.php_code_transformer';

    public function __construct(private readonly PhpCodeTransformer $transformer)
    {
        parent::__construct($this->transformer);
    }

    public function getTransformer(): PhpCodeTransformer
    {
        return $this->transformer;
    }
}
