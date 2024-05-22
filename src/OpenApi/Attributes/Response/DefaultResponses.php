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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DefaultResponses extends Response
{
    private array $defaultErrorCodes = [
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::METHOD_NOT_ALLOWED,
        HttpResponseCodes::UNSUPPORTED_MEDIA_TYPE,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ];

    public function __construct(array $codes = [])
    {
        $description = $this->generateDescription($codes);

        parent::__construct(
            response: 'default',
            description: $description,
            content: new JsonContent(
                oneOf: array_map(static function ($class) {
                    return new Schema(ref: $class);
                }, Schemas::ERRORS),
            )
        );
    }

    private function generateDescription(array $errorCodes): string
    {
        // merge the default error codes with the provided ones
        $errorCodes = array_merge($this->defaultErrorCodes, $errorCodes);

        // Sort the array of enums by http status code
        usort($errorCodes, static function($a, $b) {
            return $a->value <=> $b->value;
        });

        // Generate description block of http codes
        $errorCodes = array_map(function ($code) {
            return sprintf('%s - %s', $code->value, $this->generateNiceName($code->name));
        }, $errorCodes);

        return implode('<br>', $errorCodes);
    }

    private function generateNiceName(string $name): string {
        return ucwords(str_replace('_', ' ', strtolower($name)));
    }
}
