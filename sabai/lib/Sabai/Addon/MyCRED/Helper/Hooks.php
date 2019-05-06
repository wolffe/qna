<?php
class Sabai_Addon_MyCRED_Helper_Hooks extends Sabai_Helper
{
    public function help(Sabai $application)
    {
        if (!$hooks = $application->getPlatform()->getCache('mycred_hooks')) {
            $hooks = $application->Filter('mycred_hooks', array());
            $application->getPlatform()->setCache($hooks, 'mycred_hooks');
        }
        return $hooks;
    }
}