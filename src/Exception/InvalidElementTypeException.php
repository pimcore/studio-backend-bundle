<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

/**
 * @internal
 */
final class InvalidElementTypeException extends AbstractApiException
{
    public function __construct(string $type)
    {
        parent::__construct(400, sprintf(
            'Invalid element type: %s',
            $type
        ));
    }
}