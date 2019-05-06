<?php
class Sabai_Platform_WordPress_DBConnection extends SabaiFramework_DB_Connection
{
    protected $_wpdb;
    
    public function __construct(wpdb $wpdb)
    {
        parent::__construct($wpdb->use_mysqli ? 'MySQLi' : 'MySQL');
        $this->_resourceName = $wpdb->dbname;
        $this->_clientEncoding = $wpdb->charset;
        $this->_wpdb = $wpdb;
    }
    
    public function getWpdb()
    {
        return $this->_wpdb;
    }

    protected function _doConnect()
    {
        return $this->_wpdb->dbh;
    }

    public function getDSN()
    {
        return sprintf('%s://%s:%s@%s/%s?client_flags=%d',
            strtolower($this->_scheme),
            rawurlencode($this->_wpdb->dbuser),
            rawurlencode($this->_wpdb->dbpassword),
            rawurlencode($this->_wpdb->dbhost),
            rawurlencode($this->_wpdb->dbname),
            $this->_scheme === 'MySQL'
                ? (defined('MYSQL_CLIENT_FLAGS') ? MYSQL_CLIENT_FLAGS : 0)
                : (defined('MYSQLI_CLIENT_FLAGS') ? MYSQLI_CLIENT_FLAGS : 0)
        );
    }
}