<?php
class Sabai_Addon_Entity_Helper_Bundle extends Sabai_Helper
{
    public static $bundles = array();
    private static $_bundlesById = array();
    
    public function help(Sabai $application, $entityOrBundle)
    {
        if ($entityOrBundle instanceof Sabai_Addon_Entity_IEntity) {
            $ret = $this->_getBundleByName($application, $entityOrBundle->getBundleName());
        } elseif (is_int($entityOrBundle)) {
            $ret = $this->_getBundleById($application, $entityOrBundle);
        } elseif (is_string($entityOrBundle)) {
            $ret = $this->_getBundleByName($application, $entityOrBundle);
        } elseif ($entityOrBundle instanceof Sabai_Addon_Entity_Model_Bundle) {
            $ret = self::add($entityOrBundle);
        }
        
        return $ret;
    }
    
    protected function _getBundleByName(Sabai $application, $bundleName)
    {
        if (isset(self::$bundles[$bundleName])) return self::$bundles[$bundleName];
        
        if ($bundle = $application->getModel('Bundle', 'Entity')->name_is($bundleName)->fetchOne()) {
            return self::add($bundle);
        }
    }
    
    protected function _getBundleById(Sabai $application, $bundleId)
    {
        if (isset(self::$_bundlesById[$bundleId])) return self::$_bundlesById[$bundleId];
        
        if ($bundle = $application->getModel('Bundle', 'Entity')->fetchById($bundleId)) {
            return self::add($bundle);
        }
    }
    
    public static function add($bundle)
    {
        self::$bundles[$bundle->name] = self::$_bundlesById[$bundle->id] = $bundle;
        
        return $bundle;
    }
    
    public static function remove($bundleName)
    {
        if (isset(self::$bundles[$bundleName])) {
            $bundle = self::$bundles[$bundleName];
            unset(self::$_bundlesById[$bundle->id], self::$bundles[$bundleName]);
        }
    }
}