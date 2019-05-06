<?php
class Sabai_Addon_Field_Renderer_Text extends Sabai_Addon_Field_Renderer_AbstractRenderer
{    
    protected function _fieldRendererGetInfo()
    {
        return array(
            'field_types' => array('text', 'markdown_text'),
            'default_settings' => array('trim' => array('enable' => false, 'length' => 200, 'marker' => '...', 'link' => false), 'separator' => ''),
        );
    }
    
    public function fieldRendererGetSettingsForm($fieldType, array $settings, $view, array $parents = array())
    {
        return array(
            'trim' => array(
                '#class' => 'sabai-form-group',
                'enable' => array(
                    '#type' => 'checkbox',
                    '#title' => __('Trim text', 'sabai'),
                    '#default_value' => !empty($settings['trim']['enable']),
                ),
                'length' => array(
                    '#field_prefix' => __('Maximum number of characters:', 'sabai'),
                    '#type' => 'number',
                    '#integer' => true,
                    '#min_value' => 1,
                    '#default_value' => $settings['trim']['length'],
                    '#size' => 5,
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s[trim][enable][]"]', $this->_addon->getApplication()->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                ), 
                'marker' => array(
                    '#field_prefix' => __('Suffix text:', 'sabai'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['trim']['marker'],
                    '#size' => 10,
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s[trim][enable][]"]', $this->_addon->getApplication()->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                ),
                'link' => array(
                    '#type' => 'checkbox',
                    '#title' => __('Link to post', 'sabai'),
                    '#default_value' => !empty($settings['trim']['link']),
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s[trim][enable][]"]', $this->_addon->getApplication()->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                ),
            ),
        );
    }

    public function fieldRendererRenderField(Sabai_Addon_Field_IField $field, array $settings, array $values, Sabai_Addon_Entity_IEntity $entity)
    {
        $ret = array();
        foreach ($values as $value) {
            if (!strlen($value['value'])) continue;
            
            if (!isset($value['html'])) {
                $value['html'] = $this->_htmlize($field, $value['value'], $entity);
                if (!is_string($value['html']) || !strlen($value['html'])) {
                    continue;
                }
            }
            $ret[] = empty($settings['trim']['enable'])
                ? $this->_getContent($value['html'], $settings, $entity)
                : $this->_getTrimmedContent($value['html'], $settings['trim']['length'], $settings['trim']['marker'], !empty($settings['trim']['link']), $settings, $entity);
        }
        return implode($settings['separator'], $ret);
    }
    
    protected function _htmlize(Sabai_Addon_Field_IField $field, $value, Sabai_Addon_Entity_IEntity $entity)
    {    
        if (($widget = $field->getFieldWidget())
            && ($widget_impl = $this->_addon->getApplication()->Field_WidgetImpl($widget, true))
            && method_exists($widget_impl, 'fieldWidgetHtmlizeText')
        ) {
            $widget_info = $widget_impl->fieldWidgetGetInfo();
            $widget_settings = $field->getFieldWidgetSettings() + (array)@$widget_info['default_settings'];
            return $widget_impl->fieldWidgetHtmlizeText($field, $widget_settings, $value, $entity);
        }
        
        return $this->_addon->getApplication()->Htmlize($value);
    }
    
    protected function _getContent($html, array $settings, Sabai_Addon_Entity_IEntity $entity)
    {
        return $html;
    }
    
    protected function _getTrimmedContent($html, $length, $marker, $link, array $settings, Sabai_Addon_Entity_IEntity $entity)
    {
        if (!empty($link)) {
            return $this->_addon->getApplication()->Summarize($html, $length - mb_strlen($marker), '')
                . $this->_addon->getApplication()->Entity_Permalink($entity, array('title' => $marker, 'class' => 'sabai-trim-marker'));
        }
        return $this->_addon->getApplication()->Summarize($html, $length, $marker);
    }
}
