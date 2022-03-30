# DatabaseConnection Class
`DatabaseConnection` class is a mysql result wrapper that's been extended from `MySQLi`. This class allows you to access MySQL database servers. There are plenty useful functions in the class that you can use it in pure php applications.

# How to use this class?
Import the class `DatabaseConnection`

`require_once "~/DatabaseConnection.php";`
```
// Create an instance of the DatabaseConnection
$db = new DatabaseConnection(MYSQL_DB, MYSQL_USERNAME, MYSQL_PASSWORD, DB_NAME);

// Get list of Undeleted feeds
$feeds = $db->getArrayPairs("SELECT * FROM feeds WHERE deleted_at IS NOT NULL", 'id', 'name');

// Result
vardump($feeds);

=> [
  1 => "Brazil vs France",
  2 => "US vs Germany",
  3 => "Greece vs China",
  ...
];
```