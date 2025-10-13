# Grid

On the request level we have three main components for the grid: `Column`, `ColumnConfiguration` and `ColumnData`.

## Column
A column is a single column in the grid. It has a name, type and a locale. It is used to get the data for the column.
in addition, it has a configuration which can be used to configure the column, like the direction of the sorting

## ColumnConfiguration
A column configuration represents how the column should behave, for example if it should be sort-able or editable. 
For the column to be exportable please make sure that it can be represented as a string.

## ColumnData
A column data is the actual data for a column. It has a reference to the column and the actual data.


## Filter
A filter is a way to filter the data in the grid. One Property of getting the grid data is the `filter` property.
Here you can define `page`, `pageSize` and `includeDescendants`.

`page` is the page number of the data you want to get. 
`pageSize` is the number of items you want to get.
`includeDescendants` is a boolean value to include the descendants of the current item.

### ColumnFilter
It is also possible to filter the data by a column. This is done by adding a `columnFilter` to the `filter` property.
A `columnFilter` has a reference to the column and the value you want to filter by. Some filters do not require a 
specific column, like the `system.tag` filter. This filters will be applied to the general search query.

Available filters are:

|                   Type                   |    filterValue     |              Options               | `key` required |
|:----------------------------------------:|:------------------:|:----------------------------------:|:--------------:|
|             metadata.select              |       string       |                                    |      true      |
|              metadata.date               | object of ISO 8601 |       `from`, `to`, or `on`        |      true      |
|              metadata.input              |       string       |                                    |      true      |
|            metadata.checkbox             |      boolean       |                                    |      true      |
|            metadata.textarea             |       string       |                                    |      true      |
|             metadata.object              |      integer       |          ID of the object          |      true      |
|            metadata.document             |      integer       |         ID fo the document         |      true      |
|              metadata.asset              |      integer       |          ID fo the asset           |      true      |
|              system.string               |       string       |    Wildcard search can be used     |      true      |
|             system.datetime              | object of ISO 8601 |       `from`, `to`, or `on`        |      true      |
|                system.tag                |       object       |    `considerChildTags`, `tags`     |     false      |
|                system.pql                |       string       |             PQL Query              |     false      |
|                system.id                 |      integer       |                                    |     false      |
|                system.ids                |  array of integer  |                                    |     false      |
|              system.integer              |      integer       |                                    |      true      |
|             system.fulltext              |       string       |                                    |     false      |
|              system.boolean              |       array        |     `true`, `false` or `null`      |      true      |
|              system.number               |       object       |   `from`, `to`, `is`, `setting`    |      true      |
|              system.select               |       array        |                                    |      true      |
|        classificationstore.string        |       string       |                                    |      true      |
|         classificationstore.rbga         |  array of integer  |          `r`,`g`,`b`,`a`           |      true      |
|         classificationstore.date         | object of ISO 8601 |       `from`, `to`, or `on`        |      true      |
|    classificationstore.quantity_value    |   sting, integer   | `unitId`(string), `value`(integer) |      true      |
| classificationstore.input_quantity_value |       string       | `unitId`(string), `value`(string)  |      true      |
|        classificationstore.select        |       array        |                                    |      true      |
|       classificationstore.boolean        |       array        |     `true`, `false` or `null`      |      true      |
|               crm.consent                |       array        |         `true` or `false`          |      true      |


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
The filter value of a Classification Store looks a bit difrent. All Filter need to have a groupId and keyId
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

## Advanced Columns
Advanced columns are a special type of column that can be used to display data in a more advanced way. There are a few types of data sources for advanced columns:
- `simpleField` - a simple field in the object
- `relationField` - a relation field in the object
- `staticText` - a static text that is not related to the object

Let's take a look at the `simpleField` type. The `simpleField` call the getter method of the object. You just have to pass the `field`.
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
                  "congfig": {
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