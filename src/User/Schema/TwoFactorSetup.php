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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Two-factor setup',
    description: 'New secret for the authenticator app, not active until a first code confirms it',
    required: ['secret', 'otpauthUri'],
    type: 'object'
)]
final class TwoFactorSetup implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Secret, for manual entry', type: 'string', example: 'JBSWY3DPEHPK3PXP')]
        private readonly string $secret,
        #[Property(
            description: 'otpauth URI to show as a QR code',
            type: 'string',
            example: 'otpauth://totp/john%40pim.example.com?issuer=Pimcore&secret=JBSWY3DPEHPK3PXP'
        )]
        private readonly string $otpauthUri,
    ) {
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getOtpauthUri(): string
    {
        return $this->otpauthUri;
    }
}
