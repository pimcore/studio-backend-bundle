---
title: Field Definition Grid Resolvers
description: Custom resolvers for data object field definitions and dot notation.
---

# Field Definition Grid Resolvers

Field Definition Grid Resolvers resolve Data Object field definitions from dot notation
strings. The Studio Backend uses dot notation to address fields within complex container
structures such as localized fields, object bricks, field collections, and blocks.

For example, `objectBrickField.myBrick.name` is parsed into parts and handed to the resolver
that handles the `objectbrick` structure.

:::note

Field definition resolvers are one of several backend components needed when adding a custom
data object datatype. For the full cross-layer workflow (core registration, search index
adapter, data adapter, grid column definition, and frontend dynamic types), see the
[Adding Object Datatypes](https://github.com/pimcore/pimcore/blob/12.3/doc/20_Extending_Pimcore/13_Bundle_Developers_Guide/11_Adding_Object_Datatypes.md)
guide.

:::

## When Do You Need a Custom Resolver?

The built-in resolvers handle all standard Pimcore container types (localized fields, object
bricks, field collections, blocks). You need a custom resolver only when you introduce a
**custom container data type** that nests child field definitions in a non-standard way.

For simple (non-container) field types like text, number, or select fields, no resolver is
needed. The `DefaultResolver` already handles all top-level fields. Custom non-container data
types only need registration in the field type map, a GDI adapter, a data adapter, and a grid
column definition, as described in
[Adding Object Datatypes](https://github.com/pimcore/pimcore/blob/12.3/doc/20_Extending_Pimcore/13_Bundle_Developers_Guide/11_Adding_Object_Datatypes.md).

For dot notation usage examples, see the
[Dot Notation reference](../../04_Development_Details/01_Dot_Notation_for_Field_Definitions.md).

## Architecture Overview

The resolver system consists of the following components:

- **`ResolverInterface`** - The contract every resolver must implement.
- **`AbstractResolver`** - A base class providing common functionality (field definition lookup, sub-container checks, wrapping results).
- **`DotNotationParser`** - The parser that splits a dot notation string and iterates over all registered resolvers to find one that can handle the given parts.
- **`TaggedIteratorResolverLoader`** - Collects all resolvers tagged with `pimcore.studio_backend.field_definition_resolver` via Symfony's tagged iterator.
- **`FieldDefinitionWrapper`** - A read-only value object that wraps the resolved `Data` field definition together with its container context.

### Resolution Flow

1. The `DotNotationParser` receives a dot notation string (e.g. `localizedfields.title`) and splits it by `.`.
2. It loads all registered resolvers via the `TaggedIteratorResolverLoader`.
3. Each resolver receives the current field definitions (from the class definition) and a reference to all other resolvers (for recursive resolution).
4. The parser iterates over all resolvers and calls `canResolve()` with the dot notation parts.
5. The first resolver that returns `true` is used. Its `resolve()` method returns a `FieldDefinitionWrapper`.

## The ResolverInterface

Every field definition resolver must implement `ResolverInterface`:

```php
namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Model\DataObject\ClassDefinition\Data;

interface ResolverInterface
{
    public function getResolverName(): string;

    public function canResolve(array $dotNotationParts): bool;

    /**
     * @throws ParseException
     */
    public function resolve(array $dotNotationParts): FieldDefinitionWrapper;

    /**
     * @param array<string, Data> $fieldDefinitions
     */
    public function setFieldDefinitions(array $fieldDefinitions): void;

    /**
     * @param array<string, ResolverInterface> $resolvers
     */
    public function setResolvers(array $resolvers): void;
}
```

| Method | Purpose |
|---|---|
| `getResolverName()` | Returns a unique identifier for the resolver (e.g. `default`, `block`). Used as key when resolvers reference each other. |
| `canResolve()` | Determines whether this resolver can handle the given dot notation parts. |
| `resolve()` | Performs the actual resolution and returns a `FieldDefinitionWrapper`. |
| `setFieldDefinitions()` | Called by the parser to inject the current class field definitions. |
| `setResolvers()` | Called by the parser to inject all registered resolvers (enables recursive resolution). |

## Built-in Resolvers

The bundle ships with five resolvers:

| Resolver | Name | Handles |
|---|---|---|
| `DefaultResolver` | `default` | Simple top-level fields (single-part dot notation) |
| `LocalizedFieldResolver` | `localizedfields` | Localized fields, e.g. `localizedfields.title` |
| `BlockResolver` | `block` | Block fields, e.g. `myBlock.innerField` |
| `ObjectBrickResolver` | `objectbrick` | Object brick fields, e.g. `bricks.myBrick.fieldName` |
| `FieldCollectionResolver` | `field_collection` | Field collection fields, e.g. `collections.myCollection.fieldName` |

## Creating a Custom Resolver

### Step 1: Implement the Resolver

Extend `AbstractResolver` to get access to helper methods like `getFieldDefinition()`, `getResolvers()`, and `wrapFieldDefinition()`.

```php
<?php
declare(strict_types=1);

namespace App\FieldDefinition\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data;

final class CustomContainerResolver extends AbstractResolver
{
    public function getResolverName(): string
    {
        // Unique name for this resolver.
        return 'custom_container';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if (count($dotNotationParts) < 2) {
            return false;
        }

        try {
            $fd = $this->getFieldDefinition($dotNotationParts[0]);
        } catch (ParseException) {
            return false;
        }

        // Check whether the field definition type matches your custom container.
        return $fd instanceof Data\SomeCustomType;
    }

    /**
     * @throws ParseException
     */
    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        $containerFd = $this->getFieldDefinition($dotNotationParts[0]);

        if (!$containerFd instanceof Data\SomeCustomType) {
            throw new ParseException('Field is not of type SomeCustomType');
        }

        // Resolve the inner field from your container structure.
        $innerField = $this->resolveInnerField($containerFd, $dotNotationParts[1]);

        return $this->wrapFieldDefinition(
            fieldDefinition: $innerField,
            containerType: 'custom_container',
            fieldname: $dotNotationParts[1],
        );
    }

    private function resolveInnerField(Data\SomeCustomType $container, string $key): Data
    {
        // Your custom resolution logic here.
        // ...
    }
}
```

### Key Methods from AbstractResolver

The `AbstractResolver` base class provides the following protected helpers:

- **`getFieldDefinition(string $key): Data`** - Looks up a field definition by key from the injected class definitions. Throws `ParseException` if the key does not exist.
- **`getFieldDefinitions(): array`** - Returns all injected field definitions.
- **`getResolvers(): array`** - Returns all registered resolvers, keyed by resolver name. Useful for delegating to another resolver (e.g. the `block` resolver) for nested structures.
- **`wrapFieldDefinition(...): FieldDefinitionWrapper`** - Creates a `FieldDefinitionWrapper` with the resolved field definition and its container context. Accepts optional `subContainerType` and `subContainerKey` parameters for nested containers (e.g. localized fields inside a block).

### Step 2: Register the Resolver

Tag your service with `pimcore.studio_backend.field_definition_resolver` in your `services.yaml`:

```yaml
services:
    App\FieldDefinition\Resolver\CustomContainerResolver:
        tags: [ 'pimcore.studio_backend.field_definition_resolver' ]
```

The `TaggedIteratorResolverLoader` collects all services with this tag and makes them available to the `DotNotationParser`.

## The FieldDefinitionWrapper

The resolver's `resolve()` method must return a `FieldDefinitionWrapper`. This is a read-only value object that carries the resolved field definition along with its container context:

| Property | Type | Description |
|---|---|---|
| `fieldDefinition` | `Data` | The resolved Pimcore field definition |
| `containerType` | `string` | The type of container (e.g. `object`, `block`, `objectbrick`, `fieldcollection`, `localizedfield`) |
| `fieldname` | `string` | The name of the resolved field |
| `subContainerType` | `?string` | Optional sub-container type (e.g. `localizedfield` when a localized field is nested inside a block) |
| `subContainerKey` | `?string` | Optional sub-container key (the field name within the sub-container) |

## Handling Nested Containers

Some resolvers need to handle nested structures. For example, a field collection may contain a block, which in turn contains a localized field. The `AbstractResolver` provides `checkForSubBlockContainer()` to handle recursive block resolution within containers.

If your custom resolver deals with container types that can nest blocks or localized fields, you can delegate to the built-in resolvers:

```php
// Get the block resolver from the resolver pool
$blockResolver = $this->getResolvers()['block'];
$blockResolver->setFieldDefinitions($innerFieldDefinitions);

if ($blockResolver->canResolve($remainingParts)) {
    $result = $blockResolver->resolve($remainingParts);
}
```

This pattern is used by the built-in `ObjectBrickResolver` and `FieldCollectionResolver` to handle blocks nested within their respective containers.
