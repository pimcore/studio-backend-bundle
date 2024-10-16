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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Response;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\InvalidCredentials;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class InvalidCredentialsResponse extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: 401,
            description: 'Invalid credentials Response',
            content: new JsonContent(ref: InvalidCredentials::class)
        );
    }
}
