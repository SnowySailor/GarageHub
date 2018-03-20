To create the database and populate it with test data, run this command in the repository directory:

`cat 3241_Config.sql 3241_Test_Data.sql | mysql -u root -p`

## Connecting
```php
$db = new MySQLDataAccess($sHost, $sUsername, $sPassword, $sDatabase);
```

## Select
```select($sFields)```
```php
$aResults = $db->select('col1, col2, col3')
               ->from('table')
               ->where('col4 = ?', $param1)
               ->orderBy('col1 DESC')
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

## Update
`update($sTable, $aNewVals [, $aWhere, ...$params])`
```php
$iAffectedRows = $db->update('table', array('col1' => $newVal1, 'col2' => $newVal2), "col2 > ?", $someParam)
```
