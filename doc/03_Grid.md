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
|   metadata.date   | object of timestamp |    `from`, `to`, or `on`    |      true      |
|  metadata.input   |       string        |                             |      true      |
| metadata.checkbox |       boolean       |                             |      true      |
| metadata.textarea |       string        |                             |      true      |
|  metadata.object  |       integer       |      ID of the object       |      true      |
| metadata.document |       integer       |     ID fo the document      |      true      |
|  metadata.asset   |       integer       |       ID fo the asset       |      true      |
|   system.string   |       string        | Wildcard search can be used |      true      |
|  system.datetime  |       integer       |    `from`, `to`, or `on`    |      true      |
|    system.tag     |       object        | `considerChildTags`, `tags` |     false      |
|    system.pql     |       string        |          PQL Query          |     false      |



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
    "key": "selectKey",
    "type": "metadata.select",
    "filterValue": {
      "from": 1719792000,
      "to": 1718792000
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