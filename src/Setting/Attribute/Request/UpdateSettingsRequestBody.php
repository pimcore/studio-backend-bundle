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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class UpdateSettingsRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new Property(
                        property: 'general',
                        properties: [
                            new Property(
                                property: 'valid_languages',
                                description: 'Valid language codes (ISO 639-1)',
                                type: 'array',
                                items: new Items(type: 'string'),
                                example: ['en', 'de', 'fr']
                            ),
                            new Property(
                                property: 'fallback_languages',
                                description: 'Language fallback mapping (e.g., {"de": "en", "fr": "en"})',
                                type: 'object',
                                example: ['de' => 'en', 'fr' => 'en']
                            ),
                            new Property(
                                property: 'required_languages',
                                description: 'Required language codes (ISO 639-1)',
                                type: 'array',
                                items: new Items(type: 'string'),
                                example: ['en']
                            ),
                            new Property(
                                property: 'default_language',
                                description: 'Default system language',
                                type: 'string',
                                example: 'en'
                            ),
                            new Property(
                                property: 'domain',
                                description: 'Main domain for the system',
                                type: 'string',
                                example: ''
                            ),
                            new Property(
                                property: 'redirect_to_maindomain',
                                description: 'Redirect to main domain',
                                type: 'boolean',
                                example: false
                            ),
                            new Property(
                                property: 'debug_admin_translations',
                                description: 'Enable translation debugging',
                                type: 'boolean',
                                example: false
                            ),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'objects',
                        properties: [
                            new Property(
                                property: 'versions',
                                properties: [
                                    new Property(
                                        property: 'days',
                                        description: 'Number of days to keep object versions',
                                        type: 'integer'
                                    ),
                                    new Property(
                                        property: 'steps',
                                        description: 'Number of version steps to keep for objects',
                                        type: 'integer',
                                        example: 10
                                    ),
                                ],
                                type: 'object',
                            ),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'assets',
                        properties: [
                            new Property(
                                property: 'versions',
                                properties: [
                                    new Property(
                                        property: 'days',
                                        description: 'Number of days to keep asset versions',
                                        type: 'integer'
                                    ),
                                    new Property(
                                        property: 'steps',
                                        description: 'Number of version steps to keep for assets',
                                        type: 'integer',
                                        example: 10
                                    ),
                                ],
                                type: 'object',
                            ),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'documents',
                        properties: [
                            new Property(
                                property: 'versions',
                                properties: [
                                    new Property(
                                        property: 'days',
                                        description: 'Number of days to keep document versions',
                                        type: 'integer'
                                    ),
                                    new Property(
                                        property: 'steps',
                                        description: 'Number of version steps to keep for documents',
                                        type: 'integer',
                                        example: 10
                                    ),
                                ],
                                type: 'object',
                            ),
                            new Property(
                                property: 'error_pages',
                                properties: [
                                    new Property(
                                        property: 'default',
                                        description: 'Default error page',
                                        type: 'string',
                                        example: ''
                                    ),
                                    new Property(
                                        property: 'localized',
                                        description: 'Localized error page IDs (e.g., {"en": "1", "de": "2"})',
                                        type: 'object',
                                        example: ['en' => '', 'de' => '']
                                    ),
                                ],
                                type: 'object',
                            ),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'email',
                        properties: [
                            new Property(
                                property: 'debug',
                                properties: [
                                    new Property(
                                        property: 'email_addresses',
                                        description: 'Debug email addresses',
                                        type: 'array',
                                        items: new Items(type: 'string'),
                                        example: ['debug@example.com']
                                    ),
                                ],
                                type: 'object',
                            ),
                        ],
                        type: 'object',
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
