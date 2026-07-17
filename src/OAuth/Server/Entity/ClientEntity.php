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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * Pre-registered first-party client. `serviceUserId` is the Pimcore user the
 * client_credentials grant acts as, so its tokens resolve to a real user.
 *
 * @internal
 */
final class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    /**
     * @param string|string[] $redirectUri
     */
    public function __construct(
        string $identifier,
        string $name,
        string|array $redirectUri,
        bool $isConfidential = false,
        private readonly ?int $serviceUserId = null,
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isConfidential = $isConfidential;
    }

    public function getServiceUserId(): ?int
    {
        return $this->serviceUserId;
    }
}
