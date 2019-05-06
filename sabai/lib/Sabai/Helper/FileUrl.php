<?php
class Sabai_Helper_FileUrl extends Sabai_Helper
{
    public function help(Sabai $application, $file, $addon = null)
    {     
        if (isset($addon)) {
            $file = $application->getAddonPath($addon) . '/' . $file;
        }   
        $site_path = $application->SitePath();
        $site_url = $application->getPlatform()->getSiteUrl();
        return $site_path !== '/' ? str_replace($site_path, $site_url, $file) : $site_url . $file;
    }
}