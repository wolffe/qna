<?php
class SabaiFramework_DB_Rowset_MySQL extends SabaiFramework_DB_Rowset
{
    public function fetchColumn($index = 0)
    {
        return ($row = mysql_fetch_row($this->_rs)) ? $row[$index] : false;
    }

    public function fetchAllColumns($index = 0)
    {
        $ret = array();
        while ($row = mysql_fetch_row($this->_rs)) $ret[] = $row[$index];

        return $ret;
    }

    public function fetchRow()
    {
        return mysql_fetch_row($this->_rs);
    }

    public function fetchAssoc()
    {
        return mysql_fetch_assoc($this->_rs);
    }

    public function seek($rowNum = 0)
    {
        // suppress the E_WARNING error which mysql_data_seek() produces upon failure
        return @mysql_data_seek($this->_rs, $rowNum);
    }

    public function rowCount()
    {
        return mysql_num_rows($this->_rs);
    }
}