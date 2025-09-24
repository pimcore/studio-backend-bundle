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

| Type                                     | filterValue        | Options                            | `key` required |
|:----------------------------------------:|:------------------:|:----------------------------------:|:--------------:|
| metadata.select                          | string             |                                    | true           |
| metadata.date                            | object of ISO 8601 | `from`, `to`, or `on`              | true           |
| metadata.input                           | string             |                                    | true           |
| metadata.checkbox                        | boolean            |                                    | true           |
| metadata.textarea                        | string             |                                    | true           |
| metadata.object                          | integer            | ID of the object                   | true           |
| metadata.document                        | integer            | ID fo the document                 | true           |
| metadata.asset                           | integer            | ID fo the asset                    | true           |
| system.string                            | string             | Wildcard search can be used        | true           |
| system.datetime                          | object of ISO 8601 | `from`, `to`, or `on`              | true           |
| system.tag                               | object             | `considerChildTags`, `tags`        | false          |
| system.pql                               | string             | PQL Query                          | false          |
| system.id                                | integer            |                                    | false          |
| system.ids                               | array of integer   |                                    | false          |
| system.integer                           | integer            |                                    | true           |
| system.fulltext                          | string             |                                    | false          |
| system.boolean                           | array              | `true`, `false` or `null`          | true           |
| system.number                            | object             | `from`, `to`, `is`, `setting`      | true           |
| system.select                            | srray              |                                    | true           |
| classificationstore.string               | string             |                                    | true           |
| classificationstore.rbga                 | array of integer   | `r`,`g`,`b`,`a`                    | true           |
| classificationstore.date                 | object of ISO 8601 | `from`, `to`, or `on`              | true           |
| classificationstore.quantity_value       | sting, integer     | `unitId`(string), `value`(integer) | true           |
| classificationstore.input_quantity_value | string             | `unitId`(string), `value`(string)  | true           |
| classificationstore.select               | array              |                                    | true           |
| classificationstore.boolean              | array              | `true`, `false` or `null`          | true           |


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

