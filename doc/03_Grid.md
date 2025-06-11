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

|       Type        |     filterValue     |           Options           | `key` required |
|:-----------------:|:-------------------:|:---------------------------:|:--------------:|
|  metadata.select  |       string        |                             |      true      |
|   metadata.date   | object of ISO 8601  |    `from`, `to`, or `on`    |      true      |
|  metadata.input   |       string        |                             |      true      |
| metadata.checkbox |       boolean       |                             |      true      |
| metadata.textarea |       string        |                             |      true      |
|  metadata.object  |       integer       |      ID of the object       |      true      |
| metadata.document |       integer       |     ID fo the document      |      true      |
|  metadata.asset   |       integer       |       ID fo the asset       |      true      |
|   system.string   |       string        | Wildcard search can be used |      true      |
|  system.datetime  | object of ISO 8601  |    `from`, `to`, or `on`    |      true      |
|    system.tag     |       object        | `considerChildTags`, `tags` |     false      |
|    system.pql     |       string        |          PQL Query          |     false      |
|     system.id     |       integer       |                             |     false      |
|  system.integer   |       integer       |                             |      true      |
|  system.fulltext  |       string        |                             |     false      |
|  system.boolean   |       boolean       |                             |      true      |



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

## Advanced Columns
Advanced columns are a special type of column that can be used to display data in a more advanced way. There are a few types of data sources for advanced columns:
- `simpleField` - a simple field in the object
- `relationField` - a relation field in the object
- `staticText` - a static text that is not related to the object
- `existingColumnName` - Can be used to reference an existing advanced column

Let's take a look at the `simpleField` type. The `simpleField` call the getter method of the object. You just have to pass the `field` To make sure the value works with the transformers, it has to be convertable to string. 
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
                  "field": "name"
                },
                {
                  "field": "productionYear"
                }
            ]
        }
    }
]
...
```

The `relationField` is a relation field in the object. You can pass the `relation` `and `field` to get the value of the relation. 
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
                "relation": "manufacturer",
                "field": "name"
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
                "text": "My Custom Text",
                }
            ]
        }
    }
]
...
```

The `existingColumnName` can be used to reference an existing advanced column. This is useful if you want to use the same advanced column in multiple places. You just have to pass the `existingColumnName` of the existing advanced column. The referenced collum has to be resolved before you call it, so mak sure the order it correct. 

This example shows how to use the `existingColumnName`. It will take the value of the `advancedName` column and use it in the `advanced` column. 
```json
...
"columns": [
    {
        "key": "advancedName",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                "field": "name",
                }
            ]
        }
    },
    {
        "key": "advanced",
        "locale": "en",
        "type": "dataobject.advanced",
        "config": {
            "advancedColumns": [
                {
                "text": " Custom Text",
                },
                {
                "existingColumnName": "advancedName"
                }
            ]
        }
    }
]
...
```

All types of advanced columns can be used together. You can also use the `relation`, `field`, `existingColumnName` together with the `staticText`. All given values will be displayed in the same column. They will be concatenated by defaults with a `-` and the order of the columns will be the same as the order of the given values. 
The concatenation value can be changed by applying a ConcatenationTransformer.

### Concatenation Symbol
To combine the values of the advanced columns, you can use the `concatenationSymbol`. This symbol will be used to concatenate the values of the advanced columns. By default, it is set to `-`. You can change it by setting the `concatenationSymbol` in the `config` of the advanced column.

### Transformers
Transformers can be applied to advanced columns to modify the output. For example, you can use the `uppercase` Transformer to change all values to uppercase.
The transformer will be applied to all data sources of the advanced column separately.

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
                "text": "My Custom Text",
                }
            ]
            "transformers": [
                {
                  "key": "uppercase"
                }
            ]
        }
    }
]
...
```

