<?php
abstract class SabaiFramework_DB
{
    /**
     * @var SabaiFramework_DB_Connection
     */
    protected $_connection;
    /**
     * @var string
     */
    protected $_resourcePrefix;

    /**
     * Constructor
     *
     * @param SabaiFramework_DB_Connection $connection
     */
    protected function __construct(SabaiFramework_DB_Connection $connection, $resoucePrefix = '')
    {
        $this->_connection = $connection;
        $this->_resourcePrefix = $resoucePrefix;
    }

    /**
     * Gets the database connection object
     *
     * @return SabaiFramework_DB_Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Gets the name of prefix used in datasource
     *
     * @return string
     */
    public function getResourcePrefix()
    {
        return $this->_resourcePrefix;
    }

    /**
     * Begins a transaction
     *
     * @throws SabaiFramework_DB_QueryException
     */
    public function begin()
    {
        $this->exec('BEGIN');
    }

    /**
     * Commits a transaction
     *
     * @throws SabaiFramework_DB_QueryException
     */
    public function commit()
    {
        $this->exec('COMMIT');
    }

    /**
     * Performs a rollback of transaction
     *
     * @throws SabaiFramework_DB_QueryException
     */
    public function rollback()
    {
        $this->exec('ROLLBACK');
    }

    /**
     * Queries the database
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return SabaiFramework_DB_Rowset
     * @throws SabaiFramework_DB_QueryException
     */
    public function query($sql, $limit = 0, $offset = 0)
    {
        $query = $this->getQuery($sql, $limit, $offset);
        if (!$rs = $this->_doQuery($query)) {
            throw new SabaiFramework_DB_QueryException(sprintf('%s SQL: %s', $this->lastError(), $query));
        }
        return $rs;
    }

    /**
     * Executes an SQL
     *
     * @param string $sql
     * @return int The number of rows affected.
     * @throws SabaiFramework_DB_QueryException
     */
    public function exec($sql)
    {
        if (!$this->_doExec($sql)) {
            throw new SabaiFramework_DB_QueryException(sprintf('%s SQL: %s', $this->lastError(), $sql));
        }
        return $this->affectedRows();
    }
    
    public function seedRandom($seed){}

    abstract public function getQuery($sql, $limit = 0, $offset = 0);
    abstract protected function _doQuery($sql);
    abstract protected function _doExec($sql);
    abstract public function affectedRows();
    abstract public function lastInsertId($tableName, $keyName);
    abstract public function lastError();
    abstract public function escapeBool($value);
    abstract public function escapeString($value);
    abstract public function escapeBlob($value);
    abstract public function unescapeBlob($value);
    abstract public function getRandomFunc($seed = null);
}