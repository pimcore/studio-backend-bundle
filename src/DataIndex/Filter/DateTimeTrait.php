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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function sprintf;

/**
 * @internal
 */
trait DateTimeTrait
{
    private array $filterValue = [];

    public function setFilterValue(array $value): void
    {
        $this->filterValue = $value;
    }

    public function getOnAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['on'])) {
            throw new InvalidArgumentException('Filter value for "on" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['on']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "on": %s, Details: %s',
                $this->filterValue['on'],
                $e->getMessage()
            ));
        }
    }

    public function getFromAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['from'])) {
            throw new InvalidArgumentException('Filter value for "from" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['from']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "from": %s, Details: %s',
                $this->filterValue['from'],
                $e->getMessage()
            ));
        }
    }

    public function getToAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['to'])) {
            throw new InvalidArgumentException('Filter value for "to" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['to']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "to": %s, Details: %s',
                $this->filterValue['to'],
                $e->getMessage()
            ));
        }
    }
}
