---
title: Grid
description: "Grid system architecture: columns, filters, transformers, and configuration."
---

# Grid

The grid API has three main request-level components: `Column`, `ColumnConfiguration`, and `ColumnData`.

## Column

A column defines which data to fetch. It has a name, type, and locale, plus an optional configuration
(e.g. sort direction).

## ColumnConfiguration

A column configuration declares how the column behaves: sortable, filterable, editable, exportable.
For export support, the column value must be representable as a string.

## ColumnData

The actual data for a column. Contains a reference to the column definition and the resolved value.

## Filter

The grid `filter` property controls pagination and scope:

- `page` - the page number
- `pageSize` - the number of items per page
- `includeDescendants` - whether to include child elements

### Sort Filter

The `sortFilter` property defines the primary sort column and direction. It is an object with the following
properties:

- `key` - the column key to sort by (default: `id`)
- `direction` - sort direction, either `ASC` or `DESC` (default: `ASC`)
- `locale` - optional locale for localized columns (e.g. `en`, `de`)

```json
...
"filter": {
  "page": 1,
  "pageSize": 50,
  "sortFilter": {
    "key": "name",
    "direction": "ASC"
  }
}
...
```

#### Additional Sort Filters

Use `additionalSortFilters` to add secondary sort columns as tiebreakers after the primary `sortFilter`.
This is useful for stable pagination — when multiple rows share the same value for the primary sort column,
additional sort filters ensure a deterministic order across pages (preventing duplicate or missing items).

`additionalSortFilters` is an array of sort filter objects, each with the same structure as `sortFilter`
(`key`, `direction`, `locale`).

```json
...
"filter": {
  "page": 1,
  "pageSize": 50,
  "sortFilter": {
    "key": "type",
    "direction": "ASC"
  },
  "additionalSortFilters": [
    {
      "key": "id",
      "direction": "ASC"
    }
  ]
}
...
```

The example above sorts primarily by `type ASC`, then by `id ASC` as a tiebreaker. The resulting SQL
would be: `ORDER BY type ASC, id ASC`.

You can specify multiple additional sort filters for multi-column sorting:

```json
...
"additionalSortFilters": [
  { "key": "name", "direction": "DESC" },
  { "key": "id", "direction": "ASC" }
]
...
```

> **Note:** The `sortFilter` and `additionalSortFilters` properties are also available on
> collection endpoints (e.g. website settings, translations, notifications) that use the
> `CollectionFilterParameter` request body.

### ColumnFilter

Add a `columnFilter` to the `filter` property to filter by specific column values. Each column filter
references a column and a filter value. Some filters (e.g. `system.tag`) apply to the general search
query and do not require a specific column key.

Available filters are:

|                   Type                   |               filterValue                |                 Options                 | `key` required |
|:----------------------------------------:|:----------------------------------------:|:---------------------------------------:|:--------------:|
|              system.string               |                  string                  |       Wildcard search can be used       |      true      |
|             system.datetime              |            object of ISO 8601            |          `from`, `to`, or `on`          |      true      |
|               system.date                | object of ISO 8601<br/>will round to day |          `from`, `to`, or `on`          |      true      |
|               system.time                |              object (12:15)              |          `from`, `to`, or `on`          |      true      |
|                system.tag                |                  object                  |       `considerChildTags`, `tags`       |     false      |
|                system.pql                |                  string                  |                PQL Query                |     false      |
|                system.id                 |                 integer                  |                                         |     false      |
|                system.ids                |             array of integer             |                                         |     false      |
|              system.integer              |                 integer                  |                                         |      true      |
|             system.fulltext              |                  string                  |                                         |     false      |
|              system.boolean              |                  array                   |        `true`, `false` or `null`        |      true      |
|              system.number               |                  object                  |      `from`, `to`, `is`, `setting`      |      true      |
|              system.select               |                  array                   |                                         |      true      |
|          system.quantity_value           |                  array                   | `from`, `to`, `is`, `setting`, `unitId` |      true      |
|       system.input_quantity_value        |                  string                  |    `unitId`(string), `value`(string)    |      true      |
|               system.rgba                |                  object                  |         `r`,`g`,`b`,`a (0 - 1)`         |      true      |
|        classificationstore.string        |                  string                  |                                         |      true      |
|         classificationstore.rgba         |                  object                  |         `r`,`g`,`b`,`a(0 - 1)`          |      true      |
|         classificationstore.date         | object of ISO 8601<br/>will round to day |          `from`, `to`, or `on`          |      true      |
|       classificationstore.datetime       |            object of ISO 8601            |          `from`, `to`, or `on`          |      true      |
|         classificationstore.time         |              object (12:45)              |          `from`, `to`, or `on`          |      true      |
|    classificationstore.quantity_value    |              string, integer              | `from`, `to`, `is`, `setting`, `unitId` |      true      |
| classificationstore.input_quantity_value |                  string                  |    `unitId`(string), `value`(string)    |      true      |
|        classificationstore.select        |                  array                   |                                         |      true      |
|       classificationstore.boolean        |                  array                   |        `true`, `false` or `null`        |      true      |
|        classificationstore.number        |                  object                  |      `from`, `to`, `is`, `setting`      |      true      |
|               crm.consent                |                  array                   |            `true` or `false`            |      true      |
|           dataobject.relation            |                  array                   |    array of `type`, `ids` objects       |      true      |


### Examples:

Filter by a select column:
```json
...
"columnFilters" [
  {
    "key": "selectKey",
    "type": "metadata.select",
    "filterValue": "selectValue"
  }
]
...
```

Filter by a date column:
```json
...
"columnFilters" [
  {
    "key": "dateKey",
    "type": "metadata.date",
    "filterValue": {
      "from": "2025-04-16T22:00:09.000Z",
      "to": "2025-04-17T22:00:09.000Z"
    }
  }
]
...
```

Filter by Tags:
```json
...
"columnFilters" [
  {
    "type": "system.tag",
    "filterValue": {
      "considerChildTags": true,
      "tags": [1,2,3]
    }
  }
]
...
```

Filter by Number:
```json
...
"columnFilters" [
  {
    "type": "system.number",
    "key": "id",
    "filterValue": {
        "setting": "less",
        "to": 100
    }
  }
]
...
```

Classification Store Basic Filter Value:
The filter value of a Classification Store looks a bit different. All filters need to have a groupId and keyId
```json
...
"columnFilters" [
  {
    "key": "technicalAttributes",
    "type": "classificationstore.string",
    "filterValue": {
      "groupId": 6,
      "keyId": 12,
      "value": "filtervalue"
    }
  }
]
...
```

Filter by DataObject Relation:
The `dataobject.relation` filter allows filtering by relation fields. The filter value must be an array of objects, each containing a `type` (the element type: `asset`, `object`, or `document`) and an `ids` array with the relation IDs to filter by. This allows filtering by multiple element types at the same time.

To filter by this structure:
```json
...
"columnFilters" [
  {
    "key": "bodyStyle",
    "type": "dataobject.relation",
    "filterValue": [
      {
        "type": "object",
        "ids": [6]
      },
      {
        "type": "asset",
        "ids": [7, 9]
      }
    ]
  }
]
...
```

## Advanced Columns

Advanced columns combine multiple data sources and transformers. Data source types:

- `simpleField` - calls a getter method on the object
- `relationField` - resolves a value through a relation
- `staticText` - a fixed text value, not related to the object

The `simpleField` type calls the object's getter for the specified field:
```json
...
"columns": [
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                    "key": "simpleField",
                    "config": {
                      "field": "name"
                    }
                },
                {
                    "key": "simpleField",
                    "config": {
                      "field": "productionYear"
                    }
                }
            ]
        }
    }
]
...
```

The `relationField` is a relation field in the object. You can pass the `relation` and `field` to get the value of the relation. 
```json
...
"columns": [
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                    "key": "relationField",
                    "config": {
                      "field": "name",
                      "relation": "manufacturer",
                    }
                }
            ]
        }

    }
]
...
```

The `staticText` is a static text that is not related to the object. You can pass the `text` to get the value of the static text. 
```json
...
"columns": [
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                    "key": "staticText",
                    "config": {
                        "text": "my-static-text",
                    }
                }
            ]
        }
    }
]
...
```

### Classification Store
To display values from the Classification Store in the Grid, you must configure the corresponding `groupId` and `keyId`.
The column type has to be set to `dataobject.classificationstore`.

```json
...
"columns": [
    {
        "key": "technicalAttributes",
        "locale": null,
        "type": "dataobject.classificationstore",
        "config": {
            "groupId": 2,
            "keyId": 4
        }
    }
]
...
```

### Transformers
Transformers can be applied to advanced columns to modify the output. For example, you can use the `changeChase` Transformer to change all values to uppercase.
The transformer will be applied to all data sources of the advanced column separately.


#### ChangeChase Transformer

Available modes:
 - `uppercase` - changes all values to uppercase
 - `lowercase` - changes all values to lowercase

```json
...
"columns": [
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                    "key": "staticText",
                    "config": {
                      "text": "my-static-text",
                    }
                }
            ]
            "transformers": [
                {
                  "key": "changeChase",
                  "config": {
                    "mode": "uppercase"
                  }
                }
            ]
        }
    }
]
...
```


#### Combine Transformer

Available configurations:
- `glue` - combines the values of the advanced columns into a single string

```json
...
"columns": [
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                    "key": "staticText",
                    "config": {
                      "text": "my-static-text",
                    }
                },
                {
                    "key": "staticText",
                    "config": {
                      "text": "my-static-text",
                    }
                }
            ]
            "transformers": [
                {
                  "key": "combine",
                  "config": {
                    "glue": " - "
                  }
                }
            ]
        }
    }
]
...
```


#### TwigOperator Transformer

The `TwigOperator` transformer allows you to render custom HTML using Twig templates based on values extracted from `advancedColumns`.

---

**Available Configurations:**

- `template`: A valid Twig template string. Use `{{ value }}` to reference the column data. You can also apply Twig filters like `join`, `upper`, `lower`, `trim`, `date`, etc.
- `advancedColumns`: A list of fields to extract from the data object. Each field must be declared here to be accessible in the template.

---

**Example Configuration:**

```json
{
  "key": "summary",
  "locale": "en",
  "type": "dataobject.advanced",
  "config": {
    "title": "Summary",
    "advancedColumns": [
      { "key": "simpleField", "config": { "field": "id" } },
      { "key": "simpleField", "config": { "field": "name" } },
      { "key": "simpleField", "config": { "field": "color" } },
      { "key": "simpleField", "config": { "field": "fullpath" } },
      { "key": "simpleField", "config": { "field": "filename" } },
      { "key": "simpleField", "config": { "field": "classname" } },
      { "key": "simpleField", "config": { "field": "bodyStyle" } }
    ],
    "transformers": [
      {
        "key": "twigOperator",
        "config": {
          "template": "<h1>{{ value.name|trim }}</h1><p><strong>ID:</strong> {{ value.id }}</p>{% if value.color is iterable %}<p><strong>Available Colors:</strong></p><ul>{% for color in value.color %}<li>{{ color }}</li>{% endfor %}</ul>{% else %}<p><em>No colors available.</em></p>{% endif %}{% if value.bodyStyle is defined and value.bodyStyle.fullPath is defined %}<p><strong>Body Style:</strong> {{ value.bodyStyle.fullPath }}</p>{% endif %}<p><strong>Path:</strong> {{ value.fullpath }}</p><p><strong>Filename:</strong> {{ value.filename }}</p><p><strong>Class:</strong> {{ value.classname }}</p><hr><p><em>Generated summary for car object.</em></p>"
        }
      }
    ]
  }
}
...
```

#### Blur Transformer

The `blur` transformer allows you to obfuscate or anonymize sensitive string data using predefined strategies such as masking, initials, partial reveal, or complete hiding. It is especially useful for protecting personal information in grid views.

---

**Available Configurations:**

-   `mask`: Masks all but a portion of the string (e.g. j**\*@e\*\*\***.com).
-   `initials`: Converts names to initials (e.g. John Doe → J.D.).
-   `partial`: Shows the beginning and end of the string, masking the middle (e.g. 12\*\*\*\*7890).
-   `hide`: Replaces the entire value with [hidden].

---


**Optional Parameters:**

-   `maskChar`: Character used for masking (default: "\*").
-   `minMaskLength`: Minimum number of mask characters to apply.
-   `mask Rule Specific`: `visiblePrefix`- chars to show at start, `visibleDomainSuffix`: chars to show at end of domain, `minDomainMaskLength`: minimum mask length for domain .
-   `partial Rule Specific`: `visiblePrefix`- chars to show at start , `visibleSuffix`: chars to show at end.

---

**Example Configuration:**

```json

{
  "key": "blurredName",
  "locale": "en",
  "type": "dataobject.advanced",
  "config": {
    "title": "Blurred Name",
    "advancedColumns": [
      {
        "key": "simpleField",
        "config": {
          "field": "name"
        }
      }
    ],
    "transformers": [
      {
        "key": "blur",
        "config": {
          "rule": "partial",
          "visiblePrefix": 1,
          "visibleSuffix": 4,
          "maskChar": "*",
          "minMaskLength": 4,
          "visibleDomainSuffix": 4,
          "minDomainMaskLength": 5
        }
      }
    ]
  }
}

...
```

#### Anonymizer Transformer

The `anonymizer` transformer allows you to irreversibly obfuscate sensitive string data using cryptographic hashing strategies. This is useful when you want to store or display anonymized identifiers that cannot be reversed, such as user IDs, emails, or names.

---

**Available Configurations:**

-   `md5`: Hashes the string using the MD5 algorithm.Example: john.doe@example.com → fd876f8e4e6c3c3d3d3e3e3e3e3e3e3e
-   `bcrypt`: Hashes the string using the Bcrypt algorithm (with salt)..Example: john.doe@example.com → $2y$10$... (varies per execution)
    Note: Bcrypt is non-deterministic due to salting, so the output will differ each time it's applied.

---



**Example Configuration:**

```json

{
  "key": "anonymizedName",
  "locale": "en",
  "type": "dataobject.advanced",
  "config": {
    "title": "Anonymized Name",
    "advancedColumns": [
      {
        "key": "simpleField",
        "config": {
          "field": "name"
        }
      }
    ],
    "transformers": [
      {
        "key": "anonymizer",
        "config": {
          "rule": "bcrypt"
        }
      }
    ]
  }
}
...
```


#### Translate Transformer

The `Translate` transformer translates a given value using Symfony’s Translator component. You can optionally add a prefix to the translation key and specify a locale to control the translation context. This is useful for dynamically translating field values in grids or data objects according to the configured locale and translation keys.

---

**Available Configurations:**

-   `prefix`: Adds a prefix to the value before translation.

---

**Example Configuration:**

```json
{
  "key": "translatedName",
  "locale": "en",
  "type": "dataobject.advanced",
  "config": {
    "title": "Translated Name",
    "advancedColumns": [
      {
        "key": "simpleField",
        "config": {
          "field": "name"
        }
      }
    ],
    "transformers": [
      {
        "key": "translate",
        "config": {
          "prefix": "attribute.",
        }
      }
    ]
  }
}
...
```

#### PhpCode Transformer

The `PhpCode` transformer delegates value transformation to a custom PHP class implementing the Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface. This allows developers to encapsulate complex transformation logic in reusable services. 
To register a transformer, the service must be tagged with the appropriate Symfony service tag (studio_backend.grid.php_code_transformer) so it can be discovered by the system.
Developers can write their own PHP services to handle complex transformations. This is helpful when the logic needs to use other services or custom settings.

---

**Available Configurations:**

-   `phpCodeKey`: The fully qualified key value of the PHPCode transformer to execute. Must implement PhpCodeTransformerInterface.

---

**Example Configuration:**

```json
{
  "key": "phpCodeTransformedName",
  "locale": "en",
  "type": "dataobject.advanced",
  "config": {
    "title": "PHP Code Transformed Name",
    "advancedColumns": [
      {
        "key": "simpleField",
        "config": {
          "field": "name"
        }
      }
    ],
    "transformers": [
          {
            "key": "phpCode",
            "config": {
              "phpCodeKey": "phpCodeTransformerKeyValue",
            }
          }
        ]
  }
}

...
```