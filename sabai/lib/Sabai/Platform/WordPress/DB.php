<?php
class Sabai_Platform_WordPress_DB extends SabaiFramework_DB_MySQL
{
    protected $_affectedRows;
    
    public function __construct(Sabai_Platform_WordPress_DBConnection $connection)
    {
        parent::__construct($connection, $connection->getWpdb()->prefix . 'sabai_');
    }
    
    protected function _doQuery($query)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($query);
        $wpdb->show_errors();
        if (false === $result) return false;

        return new Sabai_Platform_WordPress_DB_Rowset($wpdb->last_result);
    }

    protected function _doExec($sql)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($sql);
        $wpdb->show_errors();
        if (false === $result) {
            $this->_affectedRows = -1;
            return false;
        }      
        
        $this->_affectedRows = $result;
        return true;
    }

    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    public function lastInsertId($tableName, $keyName)
    {
        return $this->_connection->getWpdb()->insert_id;
    }

    public function lastError()
    {
        return $this->_connection->getWpdb()->last_error;
    }

    public function escapeString($value)
    {
        return "'" . $this->_connection->getWpdb()->_real_escape($value) . "'";
    }
}

class Sabai_Platform_WordPress_DB_Rowset extends SabaiFramework_DB_Rowset
{
    protected $_rowIndex = 0;
    
    public function fetchColumn($index = 0)
    {
        if (!isset($this->_rs[$this->_rowIndex])) return false;
        
        $keys = array_keys((array)$this->_rs[$this->_rowIndex]);
        $key = $keys[$index];
        return $this->_rs[$this->_rowIndex]->$key;
    }

    public function fetchAllColumns($index = 0)
    {
        if (!isset($this->_rs[0])) return array();
        
        $keys = array_keys((array)$this->_rs[0]);
        $key = $keys[$index];
        $ret = array($this->_rs[0]->$key);
        $count = count($this->_rs);
        for ($i = 1; $i < $count; ++$i) {
            $ret[] = $this->_rs[$i]->$key;
        }

        return $ret;
    }

    public function fetchRow()
    {
        return array_values((array)$this->_rs[$this->_rowIndex]);
    }

    public function fetchAssoc()
    {
        return (array)$this->_rs[$this->_rowIndex];
    }

    public function seek($rowNum = 0)
    {
        $this->_rowIndex = $rowNum;
        return isset($this->_rs[$this->_rowIndex]);
    }

    public function rowCount()
    {
        return count($this->_rs);
    }
}