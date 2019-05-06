<?php
abstract class SabaiFramework_DB_Connection
{
    /**
     * @var string
     */
    protected $_scheme;
    /**
     * @var resource
     */
    protected $_resourceId;
    /**
     * @var string
     */
    protected $_resourceName;
    /**
     * @var string
     */
    protected $_clientEncoding;

    /**
     * Constructor
     *
     * @param string $scheme
     */
    protected function __construct($scheme)
    {
        $this->_scheme = $scheme;
    }
    
    /**
     * Establishes connection with the data source
     * 
     * @return resource Resource identifier of the data source
     * @throws SabaiFramework_DB_ConnectionException
     */
    public function connect()
    {
        if (!isset($this->_resourceId)) {
            $this->_resourceId = $this->_doConnect();
        }
        
        return $this->_resourceId;
    }

    /**
     * Gets the name of database scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    /**
     * Gets the name of data source
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->_resourceName;
    }
    
    /**
     * Get the client connection encoding
     * 
     * @return string
     */
    public function getClientEncoding()
    {
        return $this->_clientEncoding;
    }
    
    /**
     * Magic method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getDSN();
    }

    abstract protected function _doConnect();
    abstract public function getDSN();
}