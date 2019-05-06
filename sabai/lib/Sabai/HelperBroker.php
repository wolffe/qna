<?php
class Sabai_HelperBroker extends SabaiFramework_Application_HelperBroker
{    
    public function helperExists($name)
    {
        if (strpos($name, '_', 1)) {
            // Search addon's helper directory
            if (($name_arr = explode('_', $name))
                && $this->_application->isAddonLoaded($name_arr[0])
            ) {
                $class = 'Sabai_Addon_' . $name_arr[0] . '_Helper_' . $name_arr[1];
                if (!class_exists($class, false)) {
                    require $this->_application->getAddonPath($name_arr[0]) . '/Helper/' . $name_arr[1] . '.php';
                }
                $helper = new $class($this->_application);
                if (isset($name_arr[2])) {
                    $this->setHelper($name, array($helper, $name_arr[2]))
                        ->setHelper($name_arr[0] . '_' . $name_arr[1], array($helper, 'help'));
                } else {
                    $this->setHelper($name, array($helper, 'help'));
                }

                return true;
            }
        } else {
            // Core helper requested
            if (parent::helperExists($name)) return true;
        }
        
        // Is it a normal function?
        if (function_exists($name)) {
            $this->setHelper($name, $name);
        }

        return false;
    }
}