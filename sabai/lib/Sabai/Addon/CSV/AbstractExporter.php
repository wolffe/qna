<?php
abstract class Sabai_Addon_CSV_AbstractExporter implements Sabai_Addon_CSV_IExporter
{
    protected $_application, $_name, $_info;

    public function __construct(Sabai $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function csvExporterInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_csvExporterInfo();
        }

        return isset($key) ? @$this->_info[$key] : $this->_info;
    }
    
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array()){}
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        if (!empty($columns)) {
            $ret = array();
            foreach ($columns as $column) {
                $ret[$column] = isset($value[0][$column]) ? $value[0][$column] : '';
            }
            return $ret;
        }
        return $value[0]['value'];
    }
    
    public function csvExporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        return true;
    }

    protected function _csvExporterInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }
    
    protected function _acceptMultipleValues($enclosure, array $parents, array $reserved = array(), $defaultSeparator = ';')
    {
        return array(
            '_separator' => array(
                '#type' => 'textfield',
                '#title' => __('Field value separator', 'sabai'),
                '#size' => 5,
                '#description' => __('Enter the character that will be used to separate multiple values in case the field contains more than one value.', 'sabai'),
                '#min_length' => 1,
                '#default_value' => $defaultSeparator,
                '#element_validate' => array(array(array($this, '_validateSeparator'), array($enclosure, $parents, $reserved))),
                '#weight' => 100,
                '#required' => true,
            ),
        );
    }
    
    public function _validateSeparator(Sabai_Addon_Form_Form $form, &$value, $element, $enclosure, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);        
        $value = trim($value);
        if ($value == $enclosure) {
            $form->setError(sprintf(__('Field value separator may not be the same as %s.', 'sabai'), __('CSV file field enclosure', 'sabai')), $element);
        }
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && $value == $form_values[$field_name]
                ) {
                    $form->setError(sprintf(__('Field value separator may not be the same as %s.', 'sabai'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getDateFormatSettingsForm(array $parents, array $reserved = array(), $defaultDateFormatPhp = null)
    {
        return array(
            'date_format' => array(
                '#type' => 'select',
                '#title' => __('Date and time format', 'sabai'),
                '#description' => __('Select the format used to represent date and time values in CSV.', 'sabai'),
                '#options' => array(
                    'timestamp' => __('Timestamp', 'sabai'),
                    'string' => __('Formatted date/time string', 'sabai'),
                ),
                '#default_value' => 'timestamp',
            ),
            'date_format_php' => array(
                '#type' => 'textfield',
                '#title' => __('PHP date and time format', 'sabai'),
                '#description' => __('Enter the data/time format string suitable for input to PHP date() function.', 'sabai'),
                '#default_value' => isset($defaultDateFormatPhp) ? $defaultDateFormatPhp : 'Y-m-d',
                '#element_validate' => array(array(array($this, '_validateDateFormatPhp'), array($parents, $reserved))),
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[date_format]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'string'),
                    ),
                ),
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'date_format\')) === \'string\';', implode("', '", $parents))),
            ),
        );
    }
    
    public function _validateDateFormatPhp(Sabai_Addon_Form_Form $form, &$value, $element, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);
        
        if ($form_values['date_format'] !== 'string') return;
        
        if (isset($form_values['_separator']) && strlen($form_values['_separator'])) { 
            if (false !== strpos($value, $form_values['_separator'])) {
                $form->setError(sprintf(__('PHP date format may not contain %s.', 'sabai'), __('Field value separator', 'sabai')), $element);
            }
        }
        
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && false !== strpos($value, $form_values[$field_name])
                ) {
                    $form->setError(sprintf(__('PHP date and time format may not contain %s.', 'sabai'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getZipFileSettingsForm()
    {
        if (!class_exists('ZipArchive', false)) return array();
        
        return array('zip' => array(
            '#type' => 'checkbox',
            '#title' => __('Generate zip archive', 'sabai'),
            '#default_value' => true,
        ));
    }
    
    protected function _doZipFile(array $settings)
    {
        return !empty($settings['zip']);
    }
    
    protected function _getZipFile($fieldName, array $settings)
    {
        if (!class_exists('ZipArchive', false)) return false;
        
        $zip = new ZipArchive();
        $zip_file_path = rtrim($this->_application->getAddon('CSV')->getExportDir(), '/') . '/' . $settings['_file'] . '-' . $fieldName . '.zip';
        if (true !== $result = $zip->open($zip_file_path, ZipArchive::CREATE)) {
            $this->_application->LogError('Failed creating zip archive. Error: ' . $result);
            return false;
        }
        
        return $zip;
    }

    protected function _getUserSettingsForm()
    {
        return array(
            'id_format' => array(
                '#type' => 'select',
                '#title' => __('User identification value format', 'sabai'),
                '#description' => __('Select the format used to represent user identification values in CSV.', 'sabai'),
                '#options' => array(
                    'id' => __('User ID', 'sabai'),
                    'username' => __('Username', 'sabai'),
                ),
                '#default_value' => 'id',
            ),
        );
    }
}
