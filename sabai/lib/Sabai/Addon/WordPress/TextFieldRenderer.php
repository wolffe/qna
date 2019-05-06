<?php
class Sabai_Addon_WordPress_TextFieldRenderer extends Sabai_Addon_Field_Renderer_Text
{    
    protected function _fieldRendererGetInfo()
    {
        $ret = parent::_fieldRendererGetInfo();
        $ret['default_settings']['shortcode'] = false;
        $ret['default_settings']['trim']['marker'] = apply_filters('excerpt_more', ' ' . '[&hellip;]');
        return $ret;
    }
    
    public function fieldRendererGetSettingsForm($fieldType, array $settings, $view, array $parents = array())
    {        
        $shortcode_roles = array_intersect_key(
            $this->_addon->getApplication()->getPlatform()->getUserRoles(),
            $this->_addon->getApplication()->AdministratorRoles() + array_flip((array)$this->_addon->getConfig('shortcode_roles'))
        );
        return parent::fieldRendererGetSettingsForm($fieldType, $settings, $view, $parents) + array(
            'shortcode' => array(
                '#type' => 'checkbox',
                '#title' => __('Process shortcode(s)', 'sabai'),
                '#default_value' => $settings['shortcode'],
                '#description' => __(sprintf(
                    __('User roles allowed to use shortcodes: %s', 'sabai'),
                    implode(', ', $shortcode_roles)
                )),
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[trim][enable][]"]', $this->_addon->getApplication()->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                    ),
                ),
            ),
        );
    }
    
    protected function _getContent($html, array $settings, Sabai_Addon_Entity_IEntity $entity)
    {
        return $settings['shortcode'] ? $this->_doShortcode($html, $entity) : $html;
    }
    
    protected function _getTrimmedContent($html, $length, $marker, $link, array $settings, Sabai_Addon_Entity_IEntity $entity)
    { 
        return parent::_getTrimmedContent(strip_shortcodes($html), $length, $marker, $link, $settings, $entity);
    }
    
    protected function _doShortcode($text, Sabai_Addon_Entity_IEntity $entity)
    {        
        $author = null;
        $application = $this->_addon->getApplication();
        if (isset($this->_bundle->info['author_helper'])) {
            $author = $application->{$this->_bundle->info['author_helper']}($entity);
        }
        if (!$author) {
            if (!$entity->getAuthorId()) {
                return strip_shortcodes($text);
            }
            $author = $application->Entity_Author($entity);
        }
        $text = str_replace(array('{entity_id}', '{entity_user_id}'), array($entity->getId(), $author->id), $text);

        return $author->isAnonymous() ? strip_shortcodes($text) : $application->WordPress_DoShortcode($text, $author);
    }
}
