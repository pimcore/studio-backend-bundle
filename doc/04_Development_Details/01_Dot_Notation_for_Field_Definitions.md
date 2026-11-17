---
title: Dot Notation for Field Definitions
description: How nested field paths are represented in API requests and responses.
---

# Dot Notation for Field Definitions

The Studio Backend uses dot notation to resolve field definitions within complex data object
structures. Examples using the Car object:
- `carClass` Get the Standard fields.
- `localizedfields.name` Get Localized Fields
- `attributes.Bodywork.numberOfDoors` Get Field from a Brick

More complex examples using the News object:
- `content.NewsCars.relatedCars` Get Field from Field Collection
- `content.NewsCars.localizedfields.title` Get Localized Field from Field Collection
- `content.NewsLinks.links.link` Get Field from a Block in a Field Collection

Keys of a classification store are addressed by id, because their name is not unique across groups:
- `technicalAttributes.3.6` Get the key with id `6` of group `3` in the classification store field
  `technicalAttributes`.
- `technicalAttributes.3.en.6` The same key, with the language it is edited in. The language
  comparison view sends this form; the key definition does not depend on the language, so the part
  is ignored.

The group has to belong to the store configured on the classification store field, otherwise the
path is rejected.


## Custom Data Types
When you have custom Container Data Type and would like to support the Dot Notation you can implement your own Resolver.
You need to Extend `Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\AbstractResolver`

Here is a simple Example: 

```php
<?php
declare(strict_types=1);


namespace App\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;

/**
 * @internal
 */
final class DefaultResolver extends AbstractResolver
{

    public function getResolverName(): string
    {
        return 'my_resolver';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if (count($dotNotationParts) === 1) {
            return true;
        }

        return false;
    }


    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        $key = $dotNotationParts[0];

        return $this->wrapFieldDefinition(
            fieldDefinition: $this->getFieldDefinition($key),
            containerType: 'my_container',
            fieldname: $key,
        );
    }
}

```
