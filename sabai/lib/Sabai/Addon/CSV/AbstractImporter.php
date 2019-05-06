<?php
abstract class Sabai_Addon_CSV_AbstractImporter implements Sabai_Addon_CSV_IImporter
{
    protected $_application, $_name, $_info;

    public function __construct(Sabai $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function csvImporterInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_csvImporterInfo();
        }

        return isset($key) ? @$this->_info[$key] : $this->_info;
    }
    
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array()){}
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        return array(array($column => $value));
    }
    
    public function csvImporterClean(Sabai_Addon_Entity_Model_Field $field, array $settings, $column){}
    
    public function csvImporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        return true;
    }

    protected function _csvImporterInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }
    
    protected function _acceptMultipleValues($enclosure, array $parents, array $reserved = array(), $defaultSeparator = ';')
    {
        return array(
            '_multiple' => array(
                '#type' => 'checkbox',
                '#title' => __('Column contains multiple values', 'sabai'),
                '#description' => __('Check this option if the CSV column contains multiple values to be imported. Make sure the field associated accepts multiple values.'),
                '#default_value' => true,
                '#weight' => 100,
            ),
            '_separator' => array(
                '#type' => 'textfield',
                '#title' => __('Column value separator', 'sabai'),
                '#size' => 5,
                '#description' => __('Enter the character used to separate multiple values in the column.', 'sabai'),
                '#min_length' => 1,
                '#default_value' => $defaultSeparator,
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'multiple\')) ? true : false;', implode("', '", $parents))),
                '#element_validate' => array(array(array($this, '_validateSeparator'), array($enclosure, $parents, $reserved))),
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[_multiple][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true), 
                    ),
                ),
                '#weight' => 101,
            ),
        );
    }
    
    public function _validateSeparator(Sabai_Addon_Form_Form $form, &$value, $element, $enclosure, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);
        if (empty($form_values['_multiple'])) return;
        
        $value = trim($value);
        if ($value == $enclosure) {
            $form->setError(sprintf(__('Column value separator may not be the same as %s.', 'sabai'), __('CSV file field enclosure', 'sabai')), $element);
        }
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && $value == $form_values[$field_name]
                ) {
                    $form->setError(sprintf(__('Column value separator may not be the same as %s.', 'sabai'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getDateFormatSettingsForm()
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
        );
    }
    
    protected function _getFileLocationSettingsForm(array $parents)
    {
        if (!$this->_application->isAddonLoaded('File')) return;

        return array(
            'location' => array(
                '#type' => 'radios',
                '#title' => __('File location', 'sabai'),
                '#options' => array(
                    'upload' => __('Upload zip archive', 'sabai'),
                    'local' => __('Local folder', 'sabai'),
                    'none' => __('No upload', 'sabai'),
                ),
                '#options_description' => array(
                    'upload' => __('Upload a zip archive file containing all files specified in CSV.', 'sabai'),
                    'local' => __('Specify the path to the directory where all files specified in CSV are located.', 'sabai'),
                    'none' => sprintf(
                        __('Files already exist under %s and only importing file data.', 'sabai'),
                        $file_addon->getUploadDir()
                    ),
                ),
                '#default_value' => 'none',
            ),
            'file' => array(
                '#type' => 'file',
                '#title' => __('Upload zip archive', 'sabai'),
                '#upload_dir' => $file_addon->getTmpDir(),
                '#allowed_extensions' => array('zip'),
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[location]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => 'upload'),
                    ),
                ),
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'location\')) === \'upload\';', implode("', '", $parents))),
            ),
            'local' => array(
                '#title' => __('Local folder', 'sabai'),
                '#type' => 'textfield',
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[location]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => 'local'),
                    ),
                ),
                '#placeholder' => '/path/to/local/folder',
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'location\')) === \'local\';', implode("', '", $parents))),
            ),
            'separator' => array(
                '#type' => 'textfield',
                '#title' => __('File data separator', 'sabai'),
                '#description' => __('Enter the character used to separate data of each file.', 'sabai') . ' ' 
                    . __('This is usually required only for CSV data exported by Sabai. Leave it as it is if you are unsure.', 'sabai'),
                '#default_value' => '|',
                '#min_length' => 1,
                '#required' => true,
            ),
        );
    }
}