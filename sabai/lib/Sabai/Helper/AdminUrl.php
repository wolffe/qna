<?php
class Sabai_Helper_AdminUrl extends Sabai_Helper
{
    public function help(Sabai $application, $route = '/', array $params = array(), $fragment = '', $separator = '&amp;', $forceTrailingSlash = false)
    {
        return $application->Url(array(
            'route' => $route,
            'script' => 'admin',
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
            'force_trailing_slash' => $forceTrailingSlash,
        ));
    }
}