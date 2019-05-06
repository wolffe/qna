<?php
class Sabai_Addon_Taxonomy_SelectFieldWidget extends Sabai_Addon_Field_Widget_AbstractWidget
{
    protected function _fieldWidgetGetInfo()
    {
        return array(
            'label' => __('Select list', 'sabai'),
            'field_types' => array('taxonomy_terms'),
            'accept_multiple' => false,
            'default_settings' => array('split' => false),
            'repeatable' => true,
        );
    }

    public function fieldWidgetGetForm(Sabai_Addon_Field_IField $field, array $settings, Sabai_Addon_Entity_Model_Bundle $bundle, $value = null, Sabai_Addon_Entity_IEntity $entity = null, array $parents = array(), $admin = false)
    {
        if (!$bundle = $this->_getFieldBundle($field)) return;
        
        $default_text = sprintf(__('Select %s', 'sabai'), $this->_addon->getApplication()->Entity_BundleLabel($bundle, true));
        $split = !empty($settings['split']) && !empty($bundle->info['taxonomy_hierarchical']);
        $split = $this->_addon->getApplication()->Filter('sabai_taxonomy_select_field_split', $split, array($field, $bundle));
        $ret = array(
            '#type' => 'select',
            '#empty_value' => '',
            '#default_value' => isset($value) ? $value->getId() : null,
            '#multiple' => false,
            '#bundle' => $bundle,
            '#options' => $this->_getTermList($bundle, $default_text, 0, !$split),
        );
        if ($split) {
            if ($max_depth = $this->_addon->getApplication()->getModel(null, 'Taxonomy')->getGateway('Term')->getMaxDepth($bundle->name)) {
                    if (isset($value) && !isset($ret['#options'][$value->getId()])) {
                        $default_values = array();
                        foreach ($this->_addon->getApplication()->getModel('Term', 'Taxonomy')->fetchParents($value->getId()) as $parent) {
                            $default_values[] = $parent->id;
                        }
                        $default_values[] = $value->getId();
                        $ret['#default_value'] = $default_values[0];
                    }
                    $ret = array(
                        0 => array('#weight' => 0, '#class' => 'sabai-taxonomy-term-0') + $ret,
                        '#element_validate' => array(array($this, 'validateTaxonomySelect')),
                        '#class' => 'sabai-form-inline',
                    );
                    $url = $this->_addon->getApplication()->MainUrl('/sabai/taxonomy/child_terms', array('bundle' => $bundle->name, Sabai_Request::PARAM_CONTENT_TYPE => 'json'), '', '&');
                    for ($i = 1; $i <= $max_depth; $i++) {
                        $ret[$i] = array(
                            '#type' => 'select',
                            '#class' => 'sabai-hidden sabai-taxonomy-term-' . $i,
                            '#attributes' => array('data-load-url' => $url),
                            '#states' => array(
                                'load_options' => array(
                                    sprintf('.sabai-taxonomy-term-%d select', $i - 1) => array('type' => 'selected', 'value' => true, 'container' => '.sabai-form-fields'),
                                ),
                            ),
                            '#options' => array('' => $default_text),
                            '#states_selector' => '.sabai-taxonomy-term-' . $i,
                            '#skip_validate_option' => true,
                            '#weight' => $i,
                            '#default_value' => isset($default_values[$i]) ? $default_values[$i] : null,
                            '#field_prefix' => $this->_addon->getApplication()->getPlatform()->isLanguageRTL() ? '&nbsp;&laquo;' : '&nbsp;&raquo;',
                        );
                    }
                }
        }

        return $ret;
    }
    
    protected function _getTermList($bundle, $defaulText = '', $parent = 0, $loadChildren = true, $prefix = '-')
    {
        $ret = array('' => $defaulText);
        $terms = $this->_addon->getApplication()->Taxonomy_Terms($bundle->name);
        if (!empty($terms[$parent])) {
            if ($loadChildren) {
                $this->_getChildTermList($terms, $prefix, $parent, $ret);
            } else {
                foreach ($terms[$parent] as $term) {
                    $ret[$term['id']] = $term['title'];
                }
            }
        }
        return $ret;
    }

    protected function _getChildTermList($terms, $prefix, $parent, &$ret, $depth = 0)
    {
        foreach ($terms[$parent] as $term) {
            $ret[$term['id']] = $depth ?str_repeat($prefix, $depth) . '&nbsp;' . $term['title'] : $term['title'];
            if (!empty($terms[$term['id']])) {
                $this->_getChildTermList($terms, $prefix, $term['id'], $ret, $depth + 1);
            }
        }
    }
    
    public function validateTaxonomySelect(Sabai_Addon_Form_Form $form, &$value, $element)
    {
        $new_value = null;
        while (null !== $_value = array_pop($value)) {
            if ($_value !== '') {
                $new_value = $_value;
                break;
            }
        }
        $value = $new_value;
    }
    
    public function fieldWidgetGetPreview(Sabai_Addon_Field_IField $field, array $settings)
    {
        if (!$bundle = $this->_getFieldBundle($field)) return '';
         
        return sprintf(
            '<select disabled="disabled"><option>%s</option></select>',
            sprintf(__('Select %s', 'sabai'), Sabai::h($this->_addon->getApplication()->Entity_BundleLabel($bundle, true)))
        );
    }
    
    private function _getFieldBundle($field)
    {
        return $this->_addon->getApplication()->getModel('Bundle', 'Entity')
            ->entitytypeName_is('taxonomy')
            ->id_is($field->getFieldData('bundle_id'))
            ->fetchOne();
    }

    public function fieldWidgetGetEditDefaultValueForm($fieldType, array $settings, array $parents = array())
    {
        if (!$fieldType instanceof Sabai_Addon_Entity_Model_Field) return;
        
        $bundle = $this->_getFieldBundle($fieldType);
        $default_text = sprintf(__('Select %s', 'sabai'), $this->_addon->getApplication()->Entity_BundleLabel($bundle, true));
        return array(
            '#type' => 'select',
            '#options' => $this->_getTermList($bundle, $default_text),
        );
    }
    
    public function fieldWidgetSetDefaultValue(Sabai_Addon_Field_IField $field, array $settings, array &$form, $defaultValue)
    {
        if (isset($form[0]) && !isset($form[0]['#default_value'])) {
            $form[0]['#default_value'] = $defaultValue;
        }
    }
}