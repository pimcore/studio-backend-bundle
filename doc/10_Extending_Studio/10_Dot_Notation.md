# Dot Notation for Field Definitions

Studio provides a Dot Notation to resolve the field defintion of an object. 

Here are some examples for the Car Object:
- `carClass` Get the Standard fields.
- `localizedfields.name` Get Localized Fields
- `attributes.Bodywork.numberOfDoors` Get Field from a Brick

Here are some complex examples using the News Object:
- `content.NewsCars.relatedCars` Get Field from Field Collection
- `content.NewsCars.localizedfields.title` Get Localized Field from Field Collection
- `content.NewsLinks.links.link` Get Field from a Block in a Field Collection


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
