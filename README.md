To create the database and populate it with test data, run this command in the repository directory:

`cat 3241_Config.sql 3241_Test_Data.sql | mysql -u root -p`

## Objective
This is a class function library written to allow easy use of MySQL queries without having to worry about the risk of SQL injection from malicious user inputs. It also provides a layer of abstration between the programmer and the connection, statements, and fetching of results in order to make their lives easier. Once a connection is established, any number of SQL commands can be issued, including very complex queries by the use of the `rawQuery` function.

This class library also supports query building, which means that your query doesn't need to be constructed all at once; it can be built over time and then executed once knowing the result is necessary. For example:
```php
$db = new MySQLDataAccess($sHost, $sUser, $sPassword, $sDatabase);
$db = $db->select('col1, col2')->from('table');
if ($condition1) {
    $aResults = $db->where(array('col3' => $num1))->execute('getRow');
} else {
    $aResults = $db->where(array('col3' => $num2))->execute('getRows');
}
```
Each function call returns the `MySQLDataAccess` object so that it can be used with other functions that the class contains, which allows functions to be strung together as such: `$object->function1()->function2()->function3()`

## Connecting
```php
$db = new MySQLDataAccess($sHost, $sUsername, $sPassword, $sDatabase);
```

## Select
```php
select($sFields)
```
```php
$aResults = $db->select('col1, col2, col3')
               ->from('table')
               ->where('col4 = ?', $param1)
               ->orderBy('col1 DESC')
               ->execute('getRows');
```

### Joins
INNER, LEFT, and RIGHT joins are supported.
```php
innerJoin($sTable, $sAlias = '', $aOn = [] [, ...$params]);
leftJoin($sTable [, $sAlias = '', $aOn = [], ...$params]);
rightJoin($sTable [, $sAlais = '', $aOn = [], ...$params]);
```
```php
$aResults = $db->select('t1.col1', 't2.col1')
               ->from('table1', 't1')
               ->innerJoin('table2', 't2', 't1.col1 = t2.col2')
               ->execute('getRows');
```
### Notes
1. We can set table aliases by adding a second string parameter: `...->from('table', 't')`
2. We can also pass in `array('col4' => $param1)` to `where` to get the same result
3. If you want a single row, use `'getRow'` in `execute`
    * Use 'getField' to get the first column of the first row returned. Does not return an array in this case
    * Use 'getAffectedRows' to get the number of rows affected by a query
4. The `select` function accepts '*' for all columns
5. You can use table aliases if they were set. For example:
```php
$aResuls = $db->select('t1.col1')->from('table1', 't1')->execute('getRows');
```

## Insert
```php
insert($sTable, $aVals = [])
```
```php
$iAffectedRows = $db->insert('table', array('col1' => 5, 'col2' => 'Hello', 'col3' => 'World'))->execute('getAffectedRows');
```
### Notes
1. It is possible to append `->where()` after the `insert()` call to specify a where clause

## Update
```php
update($sTable, $aNewVals = [] [, $aWhere = [], ...$params])
```
```php
$iAffectedRows = $db->update('table', array('col1' => $newVal1, 'col2' => $newVal2), "col2 > ?", $someParam)->execute('getAffectedRows');
```
### Notes
1. `...->execute()` doesn't need to take a paremeter. If it receieves no argument, it will execute and return `null`.
2. In place of the 3rd argument's string value, an associative array can be used if columns and values must be equal. For example:
```php
$db->update('table', array('col1' => $newVal1), array('col2' => $conditialValue1))->execute();
```

### Notes
1. `$aWhere` can also be passed in by calling `...->where($aWhere)` after the `update()` call. It can go in either place and have the same effect.

## Delete
```php
delete($sTable [, $aWhere = [], ...$params])
```
```php
$iAffectedRows = $db->delete('table', array('col1' => 5, 'col2' => 6))->execute('getAffectedRows');
```
### Notes
1. In place of the `$aWhere` value, we can pass in a string and parameters like we've done before. For example:
```php
$db->delete('table', 'col1 = ? AND col2 = ?', 5, 6)->execute('getAffectedRows');
```

## Raw queries
```php
rawQuery($sSql [, ...$params])
```
```php
$aResult = $db->rawQuery("select * from table where col1 = ? and col4 = ? and col3 is null", 5, 2)->execute('getRows');
```