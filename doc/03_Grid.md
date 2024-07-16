# Grid

On the request level we have three main components for the grid: `Column`, `ColumnConfiguration` and `ColumnData`.

## Column
A column is a single column in the grid. It has a name, type adn a local. It is used to get the data for the column.
in addition, it has a configuration which can be used to configure the column, like the direction of the sorting

## ColumnConfiguration
A column configuration is how the column should behave, for example if it should be sortable or editable. 

## ColumnData
A column data is the actual data for a column. It has a reference to the column and the actual data.
