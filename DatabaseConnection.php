<?php
/**
 * DatabaseConnection class
 * 
 * Simple MySQLi class extension for PHP Applications
 */
class DatabaseConnection extends mysqli
{
	/**
	 * Convert an array to a comma-separated string
	 * 
	 * @param  array $values
	 * @return string The list of `$values` as a comma-separated string
	 */
	public function commaSeparate($values) {
		return implode(', ', $values);
	}
	
	/**
	 * Perform a DELETE query on a table
	 * 
	 * @param  string $table A table name
	 * @param  array $conditions An array of conditions
	 * @return bool Whether the query was successful or not
	 * @throws new Exception If there is a SQL error with the query
	 * @see quote()
	 */
	public function delete($table, array $conditions)
	{
		$where = $this->generateWhereClause($conditions);
		$result = $this->query("DELETE FROM $table $where");
		if ($result) {
			return $result;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Check if a row exists in a table
	 * 
	 * @param  string $table The table name to search
	 * @param  array $conditions (OPTIONAL) An array of conditions for the search
	 * @param  string $field (OPTIONAL) The name of the field to retrieve as part of the query. Defaults to ID, which should be sufficient for almost all tables.
	 * @return bool Whether or not the table contains a row matching the conditions (if defined)
	 */
	public function exists($table, array $conditions = array(), $field = 'id')
	{
		$where = $this->generateWhereClause($conditions);
		$result = $this->query("SELECT $field FROM $table $where LIMIT 1");
		return $result->num_rows;
	}
	
	/**
	 * Method to retrieve a single field from a table
	 * 
	 * This method executes a SELECT to retrieve one field based on simple conditions
	 * applied to one table only; it cannot be used to perform joins.
	 * 
	 * @param  string $table A table name
	 * @param  string $field The name of the field to retrieve
	 * @param  array $conditions
	 * @return mixed The value of the retrieved field
	 * @throws new Exception If there is a SQL error with the query
	 */
	public function field($table, $field, $conditions = array())
	{
		$where = $this->generateWhereClause($conditions);
		$sql = "SELECT $field FROM $table $where";
		
		$result = $this->query($sql);

		if ($result) {
			if ($result->num_rows){
				$fields = $result->fetch_assoc();
				return $fields[$field];
			} else {
				return null;
			}
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Run a query and return the result set as an associative array
	 * 
	 * If `$flatten` is FALSE, the returned array will always have child nodes,
	 * so even for a one row result, the row will be returned under array key [0].
	 * 
	 * @param  string $query The SQL query to perform
	 * @param  bool $flatten (OPTIONAL) Whether to return the result as a flat array if there is only one row. Defaults to FALSE.
	 * @return array
	 * @throws new Exception If there is a SQL error with the query
	 */
	public function getArray($query, $flatten = false)
	{
		$result = $this->query($query);
		if ($result) {
			if ($result->num_rows == 1 && $flatten) {
				$results = $result->fetch_assoc();
			} else {
				$results = array();
				while ($row = $result->fetch_assoc()) {
					$results[] = $row;
				}
			}
			return $results;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Run a query and return the result set as an associative array where one
	 * field from the result set is used as the array keys, and another field is
	 * used as the array values.
	 * 
	 * The default key/value fields used from the result set are `id` and `name`.
	 * If the query doesn't retrieve fields by these names, or different fields
	 * are to be used, they can be specified with the additional parameters.
	 * 
	 * @param  string $query The SQL query to perform
	 * @param  string $key (OPTIONAL) The name of a field from the result set to be used as the array keys. Defaults to `id`.
	 * @param  string $value (OPTIONAL) The name of a field from the result set to be used as the array values. Defaults to `name`.
	 * @return array
	 * @throws new Exception If there is a SQL error with the query
	 */
	public function getArrayPairs($query, $key = 'id', $value = 'name')
	{
		$result = $this->query($query);
		if ($result) {
			$results = array();
			while ($row = $result->fetch_assoc()) {
				$results[$row[$key]] = $row[$value];
			}
			return $results;
		}
		throw new Exception($this->error);
	}
		
	/**
	 * Run a query and return the array of values
	 * 
	 * This is essentially an alias for `$this->getArray($sql, true)`.
	 * 
	 * @param  string $query The SQL query to perform
	 * @return array
	 * @see    getArray()
	 */
	public function getArrayValues($query, $key) {
		$result = $this->getArray($query);
		return array_column($result, $key);
	}
	
	/**
	 * Run a query and return the result set as a multi-dimensional array where
	 * the top level array is an associative array containing rows grouped under
	 * it based on a common key in each result row (normally a foreign key).
	 * 
	 * @param  string $query The SQL query to perform
	 * @param  string $groupBy The name of a field from the result set to use as the grouping key
	 * @return array
	 * @throws new Exception If there is a SQL error with the query
	 */
	public function getGroupedArray($query, $groupBy)
	{
		$result = $this->query($query);
		if ($result) {
			$results = array();
			while ($row = $result->fetch_assoc()) {
				$results[$row[$groupBy]][] = $row;
			}
			return $results;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Run a query and return the result set as an associative array where one
	 * field from the result set is used as the array key.
	 * 
	 * The default key field used from the result set is `id`.
	 * 
	 * @param  string $query The SQL query to perform
	 * @param  string $key (OPTIONAL) The name of a field from the result set to be used as the array keys. Defaults to `id`.
	 * @return array
	 * @throws new Exception If there is a SQL error with the query
	 */
	public function getKeyedArray($query, $key = 'id')
	{
		$result = $this->query($query);
		if ($result) {
			$results = array();
			while ($row = $result->fetch_assoc()) {
				$results[$row[$key]] = $row;
			}
			return $results;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Run a query and return the first row as a single array
	 * 
	 * This is essentially an alias for `$this->getArray($sql, true)`.
	 * 
	 * @param  string $query The SQL query to perform
	 * @return array
	 * @throws new Exception If there is a SQL error with the query
	 * @see    getArray()
	 */
	public function getSingleArray($query) {
		return $this->getArray($query, true);
	}
	
	/**
	 * Run a query and return the single value of the result
	 * 
	 * @param  string $query The SQL query to perform
	 * @return mixed The value of the first (only) field returned from the query
	 * @throws new Exception If there is a SQL error with the query
	 * @see    getArray()
	 */
	public function getSingleValue($query) {
		$result = $this->getArray($query);
		if (is_array($result)) {
			return reset($result[0]);
		}
		return;
	}
	
	/**
	 * Perform an INSERT query
	 * 
	 * All values will be processed by `quote()` to escape them and wrap them in
	 * quotes.
	 * 
	 * @param  string $table A table name
	 * @param  array $data An associative array of field/value data to insert
	 * @return bool Whether the query was successful or not
	 * @throws new Exception If there is a SQL error with the query
	 * @see quote()
	 */
	public function insert($table, $data)
	{
		$fields = $this->prepInsertFields($data);
		$values = $this->prepInsertValues($data);
		$result = $this->query("INSERT INTO $table ($fields) VALUES ($values)");
		if ($result) {
			return $result;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Get a prepared list of field names for an INSERT query
	 * 
	 * @param  array $data An associative array of field/value data
	 * @return string A comma-separated list of field names for a INSERT query
	 */
	public function prepInsertFields(array $data)
	{
		$fields = array_keys($data);
		return $this->commaSeparate($fields);
	}
	
	/**
	 * Get a prepared list of field values for an INSERT query
	 * 
	 * @param  array $data An associative array of field/value data
	 * @return string A comma-separated list of escaped values for a INSERT query
	 */
	public function prepInsertValues(array $data)
	{
		$values = array_values($data);
		$list = array_map(array($this, 'quote'), $values);
		return $this->commaSeparate($list);
	}
	
	/**
	 * Get a prepared list of field values for an UPDATE query
	 * 
	 * @param  array $data An associative array of field/value data
	 * @param  bool $quote (OPTIONAL) Escape all values and wrap them in quotes by applying the `quote()` method
	 * @return string A list of fields and values for the SET parameter of an UPDATE query
	 * @see quote()
	 */
	public function prepUpdateValues(array $data, $quote = true)
    {
        $pairs = array();
        foreach ($data as $field => $value) {
            if (is_array($value)) {
                $update = $field . ' = ' . $value[0];
            } else {
                $update = $value;
                if (!is_numeric($field)) {
                    if ($quote) {
                        $value = $this->quote($value);
                    }
                    $update = $field . ' = ' . $value;
                }
            }
            $updates[] = $update;
        }
        return $this->commaSeparate($updates);
    }
	
	/**
	 * Convert a value to a MySQLi-escaped string wrapped in single quotes.
	 * 
	 * @param mixed $value
	 * @return string An escaped string wrapped in single quotes
	 */
	public function quote($value)
	{
		if ($value == 'NULL') {
			return $value;
		}
		return ("'" . $this->escape_string($value) . "'");
	}
	
	/**
	 * Perform a simple UPDATE query on one table
	 * 
	 * All values will be processed by `quote()` to escape them and wrap them in
	 * quotes.
	 * 
	 * @param  string $table A table name
	 * @param  array $data An associative array of field/value data to update
	 * @param  array $conditions (OPTIONAL) An array of conditions
	 * @return bool Whether the query was successful or not
	 * @throws new Exception If there is a SQL error with the query
	 * @see quote()
	 */
	public function update($table, $data, $conditions = array())
	{
		$values = $this->prepUpdateValues($data);
		$where = $this->generateWhereClause($conditions);
		$result = $this->query("UPDATE $table SET $values $where");
		if ($result) {
			return $result;
		}
		throw new Exception($this->error);
	}
	
	/**
	 * Generate a SQL WHERE clause from an array of conditions
	 * 
	 * @param array $conditions
	 * @return string A SQL WHERE clause which can be appended to a query
	 */
	protected function generateWhereClause($conditions) {
		$sql = '';
		if (!empty($conditions)) {
			foreach ($conditions as $key => $value) {
				if (!is_numeric($key)) {
					$operator = preg_match('/=|<|>/', $key) ? ' ' : ' = ';
					$condition = $key . $operator . $this->quote($value);
				} else {
					$condition = '(' . $value . ')';
				}
				$conditions[$key] = $condition;
			}
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}
		return $sql;
	}
}