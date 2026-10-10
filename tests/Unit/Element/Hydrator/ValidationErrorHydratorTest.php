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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\ValidationErrorHydrator;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Model\Element\ValidationPathSegment;

/**
 * @internal
 */
final class ValidationErrorHydratorTest extends Unit
{
    public function testLeafWithoutViolationsYieldsOneError(): void
    {
        $leaf = (new StructuredValidationException('Empty mandatory field [ title ]'))
            ->setTranslation(ValidationMessageKey::MANDATORY)
            ->setField('title', 'Title');

        $errors = (new ValidationErrorHydrator())->hydrate($leaf);

        $this->assertCount(1, $errors);
        $this->assertSame('title', $errors[0]->getField());
        $this->assertSame('Title', $errors[0]->getFieldTitle());
        $this->assertSame('Empty mandatory field [ title ]', $errors[0]->getMessage());
        $this->assertSame('validation.mandatory', $errors[0]->getMessageKey());
        $this->assertSame([], $errors[0]->getParameters());
        $this->assertSame([], $errors[0]->getPath());
    }

    public function testAggregateYieldsOneErrorPerViolationWithPath(): void
    {
        $first = (new StructuredValidationException('Too long'))
            ->setTranslation(ValidationMessageKey::MAX_LENGTH, ['max' => 10])
            ->setField('name')
            ->addPathSegment(new ValidationPathSegment(field: 'localizedfields', language: 'en'))
            ->addPathSegment(new ValidationPathSegment(
                field: 'attributes',
                title: 'Sale information',
                type: 'SaleInformation',
                typeTitle: 'Sale information'
            ));
        $second = new ValidationException('Plain error');
        $aggregate = (new StructuredValidationException('Validation failed'))->addViolations($first, $second);

        $errors = (new ValidationErrorHydrator())->hydrate($aggregate);

        $this->assertCount(2, $errors);
        $this->assertSame(['max' => 10], $errors[0]->getParameters());
        $this->assertNull($errors[0]->getFieldTitle());
        $this->assertCount(2, $errors[0]->getPath());
        $this->assertSame('localizedfields', $errors[0]->getPath()[0]->getField());
        $this->assertSame('en', $errors[0]->getPath()[0]->getLanguage());
        $this->assertNull($errors[0]->getPath()[0]->getTitle());
        $this->assertSame('Sale information', $errors[0]->getPath()[1]->getTitle());
        $this->assertSame('SaleInformation', $errors[0]->getPath()[1]->getType());
        $this->assertSame('Sale information', $errors[0]->getPath()[1]->getTypeTitle());

        $this->assertNull($errors[1]->getField());
        $this->assertNull($errors[1]->getFieldTitle());
        $this->assertNull($errors[1]->getMessageKey());
        $this->assertSame('Plain error', $errors[1]->getMessage());
    }

    public function testPlainValidationExceptionYieldsOneErrorWithItsMessage(): void
    {
        $errors = (new ValidationErrorHydrator())->hydrate(new ValidationException('Prevented publishing'));

        $this->assertCount(1, $errors);
        $this->assertSame('Prevented publishing', $errors[0]->getMessage());
        $this->assertNull($errors[0]->getField());
        $this->assertNull($errors[0]->getMessageKey());
        $this->assertSame([], $errors[0]->getPath());
    }

    public function testPlainExceptionWithSubItemsKeepsTheirMessages(): void
    {
        $plain = new ValidationException('invalid custom container');
        $plain->setSubItems([new ValidationException('Empty mandatory field [ a ]')]);

        $errors = (new ValidationErrorHydrator())->hydrate($plain);

        $this->assertCount(1, $errors);
        $this->assertSame($plain->getAggregatedMessage(), $errors[0]->getMessage());
        $this->assertStringContainsString('Empty mandatory field [ a ]', $errors[0]->getMessage());
    }
}
