<?php
class Sabai_Addon_CSV_Importer_FieldImporter extends Sabai_Addon_CSV_AbstractImporter
{
    protected function _csvImporterInfo()
    {
        return array(
            'field_types' => array(substr($this->_name, 6)), // remove field_ part
        );
    }
    
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        if ($this->_name === 'field_boolean') return;
        
        $form = $reserved_separator = array();
        
        switch ($this->_name) {                
            case 'field_link':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Link URL/title separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the link URL and title.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_video':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Video provider/ID separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the video provider and ID.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_range':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Field min/max separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the minimum and maximum values.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            default:
        }
        
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents, $reserved_separator);
        }
        
        return $form;
    }
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        if ($this->_name === 'field_boolean') return array(array('value' => $value));
        
        if (!empty($settings['_multiple'])) {
            if (!$values = explode($settings['_separator'], $value)) {
                return;
            }
        } else {
            $values = array($value);
        }

        $ret = array();
        
        switch ($this->_name) {
            case 'field_link':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'url' => $value[0],
                            'title' => $value[1],
                        );
                    }
                }
                break;
            case 'field_video':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'id' => $value[1],
                            'provider' => $value[0],
                        );
                    }
                }
                break;
            case 'field_range':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'min' => $value[0],
                            'max' => $value[1],
                        );
                    }
                }
                break;
            default:
                foreach ($values as $value) {
                    $ret[] = array('value' => $value);
                }
        }
        
        return $ret;
    }
}