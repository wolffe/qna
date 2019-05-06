<?php
if (!class_exists('SabaiFramework_DB_Rowset_MySQL', false)) require 'SabaiFramework/DB/Rowset/MySQL.php';

class SabaiFramework_DB_MySQL extends SabaiFramework_DB
{
    /**
     * Gets a SQL select statement
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function getQuery($sql, $limit = 0, $offset = 0)
    {
        if (intval($limit) > 0) $sql .=  sprintf(' LIMIT %d, %d', $offset, $limit);

        return $sql;
    }

    /**
     * Queries the database
     *
     * @param string $query
     * @return mixed SabaiFramework_DB_Rowset_MySQL on success, false on error
     */
    protected function _doQuery($query)
    {
        if (!$rs = mysql_query($query, $this->_connection->connect())) {
            return false;
        }

        return new SabaiFramework_DB_Rowset_MySQL($rs);
    }

    /**
     * Executes an SQL query against the DB
     *
     * @param string $sql
     * @return bool
     */
    protected function _doExec($sql)
    {
        return mysql_query($sql, $this->_connection->connect());
    }

    /**
     * Gets the primary key of te last inserted row
     *
     * @param string $tableName
     * @param string $keyName
     * @return mixed Integer or false on error.
     */
    public function lastInsertId($tableName, $keyName)
    {
        return mysql_insert_id($this->_connection->connect());
    }

    /**
     * Gets the number of affected rows
     *
     * @return int
     */
    public function affectedRows()
    {
        return mysql_affected_rows($this->_connection->connect());
    }

    /**
     * Gets the last error occurred
     *
     * @return string
     */
    public function lastError()
    {
        return sprintf('%s(%s)', mysql_error($this->_connection->connect()), mysql_errno($this->_connection->connect()));
    }

    /**
     * Escapes a boolean value for MySQL DB
     *
     * @param bool $value
     * @return int
     */
    public function escapeBool($value)
    {
        return intval($value);
    }

    /**
     * Escapes a string value for MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function escapeString($value)
    {
        return "'" . mysql_real_escape_string($value, $this->_connection->connect()) . "'";
    }

    /**
     * Escapes a blob value for MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function escapeBlob($value)
    {
        return $this->escapeString($value);
    }
    
    /**
     * Unescapes a blob value retrieved from MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function unescapeBlob($value)
    {
        return $value;
    }

    public function getRandomFunc($seed = null)
    {
        return isset($seed) ? 'RAND(' . (int)$seed . ')' : 'RAND()';
    }
}