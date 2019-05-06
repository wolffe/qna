<?php
class Sabai_Addon_MyCRED extends Sabai_Addon
{
    const VERSION = '1.4.6', PACKAGE = 'sabai';
    
    public function isUninstallable($currentVersion)
    {
        return true;
    }
    
    public function onSabaiPlatformWordpressInit()
    {
        add_action('mycred_init', array($this, 'mycredInitAction'), 9);
        add_filter('mycred_setup_hooks', array($this, 'mycredSetupHooksFilter'));
        //add_filter('mycred_all_references', array($this, 'mycredAllReferencesFilter'));
    }
    
    public function mycredInitAction()
    {
        if (!class_exists('Sabai_Addon_MyCRED_Hook', false)) {
            require dirname(__FILE__) . '/MyCRED/Hook.php';
        }
    }
    
    public function mycredSetupHooksFilter($hooks)
    {
        $hooks['hook_sabai'] = array(
            'title' => 'Sabai',
            'description' => _x('Awards %_plural% for various actions in Sabai plugins.', 'MyCRED', 'sabai'),
            'callback' => array('Sabai_Addon_MyCRED_Hook'),
        );
        return $hooks;
    }
    
    public function mycredAllReferencesFilter($hooks)
    {
        foreach ($this->_application->MyCRED_Hooks() as $hook_category => $hook) {
            $hook_category_lc = strtolower($hook_category);
            foreach ($hook['references'] as $ref_name => $ref) {
                $hooks['sabai_' . $hook_category_lc . '_' . $ref_name] = $hook_category . ' - ' . $ref['label'];
            }
        }
        return $hooks;
    }
}