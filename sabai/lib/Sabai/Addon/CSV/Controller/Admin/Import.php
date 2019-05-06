<?php
class Sabai_Addon_CSV_Controller_Admin_Import extends Sabai_Addon_Form_MultiStepController
{    
    protected $_flush;
    
    protected function _getBundle(Sabai_Context $context)
    {
        return isset($context->taxonomy_bundle) ? $context->taxonomy_bundle : (isset($context->child_bundle) ? $context->child_bundle : $context->bundle);
    }
    
    protected function _doExecute(Sabai_Context $context)
    {
        parent::_doExecute($context);
        $this->LoadCss('sabai-csv-admin.min.css', 'sabai-csv-admin', null, 'sabai');
    }
    
    protected function _getSteps(Sabai_Context $context, array &$formStorage)
    {
        return array('upload', 'map_fields', 'importer_settings', 'import');
    }
    
    public function _getFormForStepUpload(Sabai_Context $context, array &$formStorage)
    {
        $form = array(
            '#validate' => array(array(array($this, 'validateUpload'), array($context))),
            'file' => array(
                '#type' => 'file',
                '#title' => __('CSV file(s)', 'sabai'),
                '#description' => __('Select one or more CSV files to import.', 'sabai'),
                '#upload_dir' => $this->getAddon('CSV')->getImportDir(),
                '#allowed_extensions' => array('csv'),
                '#required' => true,
                // The finfo_file function used by the uploader to check mime types for CSV files is buggy. We can skip it safely here since this is for admins only.
                '#skip_mime_type_check' => true,
                '#multiple' => true,
            ),
            'delimiter' => array(
                '#type' => 'textfield',
                '#title' => __('CSV column delimiter', 'sabai'),
                '#size' => 5,
                '#description' => __('Enter the character used as CSV column delimiters.', 'sabai'),
                '#min_length' => 1,
                '#max_length' => 1,
                '#default_value' => ',',
                '#required' => true,
            ),
            'enclosure' => array(
                '#type' => 'textfield',
                '#title' => __('CSV column enclosure', 'sabai'),
                '#size' => 5,
                '#description' => __('Enter the character used as CSV column enclosures.', 'sabai'),
                '#min_length' => 1,
                '#max_length' => 1,
                '#default_value' => '"',
                '#required' => true,
            ),
            'convert_encoding' => array(
                '#type' => 'checkbox',
                '#title' => __('Convert encoding of CSV file to UTF-8', 'sabai'),
                '#default_value' => false,
            ),
            'convert_crlf' => array(
                '#type' => 'checkbox',
                '#title' => __('Convert line endings of CSV file to CR/LF', 'sabai'),
                '#default_value' => false,
            ),
        );
        
        foreach (array_keys($form) as $key) {
            if (strpos($key, '#') !== 0 ) {
                $form[$key]['#horizontal'] = true;
            }
        }
        
        return $form;
    }
    
    public function validateUpload($form, $context)
    {
        @setlocale(LC_ALL, $this->getPlatform()->getLocale());
        $convert_encoding = !empty($form->values['convert_encoding']);
        $convert_crlf = !empty($form->values['convert_crlf']);
        $delimiter = $form->values['delimiter'];
        $enclosure = $form->values['enclosure'];
        
        // Extract headers of each CSV file and a first row
        $csv_columns_sorted = null;
        foreach ($form->values['file'] as $i => $csv_file) {
            if (!isset($csv_columns_sorted)) {
                $rows = $this->_getCsvRows($csv_file, $delimiter, $enclosure, 2, $convert_encoding, $convert_crlf);
                $csv_columns_sorted = $rows[0];
                sort($csv_columns_sorted);
            } else {
                $rows = $this->_getCsvRows($csv_file, $delimiter, $enclosure, 1, $convert_encoding, $convert_crlf);
                sort($rows[0]);
                if ($rows[0] !== $csv_columns_sorted) {
                    $form->setError(sprintf(__('CSV file %s contains headers not found in other CSV file(s).', 'sabai'), $csv_file['name']));
                }
            }
        }
    }
    
    protected function _getCsvRows($file, $delimiter, $enclosure, $limit = 1, $toUtf8 = false, $toCrlf = false)
    {
        $ret = array();
        $fp = $this->File(is_array($file) ? $file['saved_file_path'] : $file, $toUtf8, $toCrlf);
        while ($limit) {
            $row = fgetcsv($fp, 0, $delimiter, $enclosure);
            if (false === $row) {
                fclose($fp);
                throw new Sabai_RuntimeException(sprintf('Failed reading row from CSV file %s.', is_array($file) ? $file['name'] : $file));
            }
            if (is_array($row)
                && array(null) !== $row
            ) {
                $ret[] = $row;
                --$limit;
            }
        }
        fclose($fp);
        return $ret;
    }
    
    public function _getFormForStepMapFields(Sabai_Context $context, array &$formStorage)
    {
        @setlocale(LC_ALL, $this->getPlatform()->getLocale());
        $convert_encoding = !empty($formStorage['values']['upload']['convert_encoding']);
        $convert_crlf = !empty($formStorage['values']['upload']['convert_crlf']);
        $delimiter = $formStorage['values']['upload']['delimiter'];
        $enclosure = $formStorage['values']['upload']['enclosure'];
        $csv_files = $formStorage['values']['upload']['file'];
        
        // Extract header and a first row
        $csv_file = array_shift($csv_files);
        $rows = $this->_getCsvRows($csv_file, $delimiter, $enclosure, 2, $convert_encoding, $convert_crlf);
        $csv_columns = $rows[0];
        $csv_row1 = $rows[1];
        
        $options = array('' => '');
        $optgroups = $custom_field_options = array();
        $importers_by_field_type = $this->CSV_Importers(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $entity_type_info = $this->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetInfo();
        $id_column_field_type = $entity_type_info['properties'][$entity_type_info['table_id_key']]['type'];
        $id_column_key = null;
        foreach ($fields as $field_name => $field) {
            if ((!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                || (!$importer = $this->CSV_Importers_impl($importer_name, true))
                || !$importer->csvImporterSupports($bundle, $field)
            ) {
                continue;
            }
                
            $columns = $importer->csvImporterInfo('columns');
            if (is_array($columns)) {
                $label_prefix = $this->_getFieldLabel($field) . ' - ';
                if ($field->isCustomField()) {
                    foreach ($columns as $column => $label) {
                        $custom_field_options[$this->_getFieldOptionValue($field_name, $column)] = $label_prefix . $label;
                    }
                } else {
                    foreach ($columns as $column => $label) {
                        $options[$this->_getFieldOptionValue($field_name, $column)] = $label_prefix . $label;
                    }
                }
            } else {
                $option = $this->_getFieldOptionValue($field_name, (string)$columns);
                if ($field->isCustomField()) {
                    $custom_field_options[$option] = $this->_getFieldLabel($field);
                } else {
                    $options[$option] = $this->_getFieldLabel($field);
                    if ($field->getFieldType() === $id_column_field_type) {
                        $id_column_key = $option;
                    }
                }
            }
        }
        asort($options);
        if (!empty($custom_field_options)) {
            asort($custom_field_options);
            $options += $custom_field_options;
        }
        
        $form = array(
            '#header' => array(
                '<div class="sabai-alert sabai-alert-info">' . Sabai::h(__('Set up the associations between the CSV file columns and content fields.', 'sabai')) . '</div>',
            ),
            '#options' => $options,
            '#optgroups' => $optgroups,
            'header' => array(
                '#type' => 'markup',
                '#markup' => '<table class="sabai-table sabai-csv-table"><thead><tr><th style="width:25%;">' . __('Column Header', 'sabai') . '</th>'
                    . '<th style="width:35%;">' . __('Row 1', 'sabai') . '</th>'
                    . '<th style="width:40%;">' . __('Select Field', 'sabai') . '</th></tr></thead><tbody>',
            ),
            'fields' => array(
                '#tree' => true,
                '#element_validate' => array(array(array($this, 'validateMapFields'), array($context, $fields, $bundle))),
            ),
            'footer' => array(
                '#type' => 'markup',
                '#markup' => '</tbody></table>',
            ),
        );
        
        foreach ($csv_columns as $column_key => $column_name) {    
            if (isset($csv_row1[$column_key])) {
                $column_value = $csv_row1[$column_key];
                if (strlen($column_value) > 300) {
                    $column_value = $this->Summarize($column_value, 300);
                }
                if (strlen($column_value)) {
                    $column_value = '<code>' . Sabai::h($column_value) . '</code>';
                }
            } else {
                $column_value = '';
            }
            $form['fields'][$column_name] = array(
                '#prefix' => '<tr><td>' . Sabai::h($column_name) . '</td><td>' . $column_value . '</td><td>',
                '#suffix' => '</td></tr>',
                '#type' => 'select',
                '#options' => $options,
                '#default_value' => isset($options[$column_name]) && $column_name !== $id_column_key ? $column_name : null,
            );
        }
        
        return $form;
    }
    
    protected function _getFieldOptionValue($fieldName, $column = '')
    {
        return strlen($column) ? $fieldName . '__' . $column : $fieldName;
    }
    
    protected function _getFieldLabel(Sabai_Addon_Field_IField $field)
    {
        $label = $field->getFieldLabel() . ' (' . $field->getFieldName() . ')';
        if ($field->isCustomField()) {
            $label = sprintf(__('Custom field - %s', '@@sabai_package_name'), $label);
        }
        return $label;
    }
    
    public function validateMapFields($form, &$value, $element, $context, $fields, $bundle)
    {
        $value = array_filter($value);
        
        $required_fields = array();
        
        if (!empty($bundle->info['parent'])) {
            $required_fields[] = $bundle->entitytype_name . '_parent';
        }
        
        // Require the title field if no ID field is being imported
        if (!in_array($this->_getFieldOptionValue($bundle->entitytype_name . '_id'), $value)) {
            $required_fields[] = $bundle->entitytype_name . '_title';
        }
        // Make sure required fields are going to be imported
        foreach ($required_fields as $field_name) {
            if (isset($fields[$field_name]) && !in_array($this->_getFieldOptionValue($field_name), $value)) {
                $form->setError(sprintf(
                    __('The following field needs to be selected: %s.', 'sabai'),
                    $this->_getFieldLabel($fields[$field_name])
                ));
            }
        }
        
        $count = array_count_values($value);
        foreach ($count as $option => $_count) {
            if ($option === '' || $_count <= 1) continue;
            
            $_option = explode('__', $option);
            $field_name = $_option[0];
            $form->setError(sprintf(
                __('You may not associate multiple columns with the field: %s', 'sabai'),
                $this->_getFieldLabel($fields[$field_name])
            ));
        }
    }
    
    public function _getFormForStepImporterSettings(Sabai_Context $context, array &$formStorage)
    {     
        $form = array('settings' => array());
        
        $enclosure = $formStorage['values']['upload']['enclosure'];
        $mapped_fields = $formStorage['values']['map_fields']['fields'];
        $fields = $this->Entity_Field($this->_getBundle($context)->name);
        $importers_by_field_type = $this->CSV_Importers(true);
        foreach ($mapped_fields as $column_name => $mapped_field) {
            if (!$_mapped_field = explode('__', $mapped_field)) continue;

            $field_name = $_mapped_field[0];
            $column = (string)@$_mapped_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                    
            $importer_name = $importers_by_field_type[$field->getFieldType()];
            if (!$importer = $this->CSV_Importers_impl($importer_name, true)) {
                continue;
            }
            $info = $importer->csvImporterInfo();
            $parents = array('settings', $field_name);
            if (strlen($column)) {
                $parents[] = $column;
            }
            if ($column_settings_form = $importer->csvImporterSettingsForm($field, (array)@$info['default_settings'], $column, $enclosure, $parents)) {
                foreach (array_keys($column_settings_form) as $key) {
                    if (strpos($key, '#') !== 0 ) {
                        $column_settings_form[$key]['#horizontal'] = true;
                    }
                }
                if (strlen($column)) {
                    $form['settings'][$field_name][$column] = $column_settings_form;
                    $form['settings'][$field_name][$column]['#title'] = $info['columns'][$column];
                    $form['settings'][$field_name][$column]['#collapsible'] = false;
                } else {
                    $form['settings'][$field_name] = $column_settings_form;
                }
                $form['settings'][$field_name]['#collapsible'] = true;
                if (!isset($form['settings'][$field_name]['#title'])) {
                    $form['settings'][$field_name]['#title'] = $this->_getFieldLabel($field);
                }
            }
        }
        if (empty($form['settings'])) {
            return $this->_skipStepAndGetForm($context, $formStorage);
        }
        
        $form['settings']['#tree'] = true;
        $form['#header'][] = '<div class="sabai-alert sabai-alert-info">' . __('Please configure additional options for each field.', 'sabai') . '</div>';
        
        return $form;
    }
    
    public function _getFormForStepImport(Sabai_Context $context, array &$formStorage)
    {
        $context->addTemplate($this->getPlatform()->getAssetsDir('sabai') . '/templates/csv_form');
        $context->results_header = __('Import Results', 'sabai');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnReadyState = 'function (result, target, trigger, count) {
    var results = target.find(".sabai-csv-results"), table = results.find("table");
    if (!result.row_num) {
        SABAI.ajaxLoader(null, true, table);
        if (result.success.length) {
            SABAI.flash(result.success, "success", 0);
        }
        if (result.error.length) {
            SABAI.flash(result.error, "danger", 0);
        }
        return;
    }
    if (count === 1) {
        target.find(".sabai-form, .sabai-form-headers").hide();
        results.slideDown();
        SABAI.scrollTo(results);
        SABAI.ajaxLoader(null, false, table);
    }
    if (result.file_changed) {
        $("<tr><th colspan=\'4\'><i class=\'fa fa-file-text-o\'></i> " + result.file + "</th></tr>").appendTo(table.find("tbody"));
    }
    $("<tr class=\'sabai-" + result.status + "\'><th>" + result.row_num + "</th><td>" + result.id + "</td><td>" + (result.title.length ? result.title : "' . Sabai::h(__('(no title)', 'sabai')) . '") + "</td><td>" + result.message + (result.is_test ? " ' . Sabai::h(__('(test)', 'sabai')) . '" : "") + "</td></tr>")
        .appendTo(table.find("tbody"));
    if (count % 5 === 0) {
        SABAI.scrollTo(results.find(".sabai-csv-results-footer"));
    }
}';
        $this->_submitButtons[] = array('#value' => __('Import Now', 'sabai'), '#btn_type' => 'primary', '#btn_size' => 'lg');
        
        return array(
            '#header' => array('<div class="sabai-alert sabai-alert-info">' . Sabai::h(__('Press the import button below to start importing.', 'sabai')) . '</div>'),
            'test' => array(
                '#type' => 'checkbox',
                '#title' => __('Test import', 'sabai'),
                '#description' => __('Check this option to test import only and not actually saving data to the database.', 'sabai'),
                '#horizontal' => true,
            ),
            'test_num' => array(
                '#type' => 'number',
                '#title' => __('Number of rows to test import', 'sabai'),
                '#description' => __('Enter the maximum number of rows to perform import test, 0 for all rows.', 'sabai'),
                '#default_value' => 0,
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        'input[name="test[]"]' => array('type' => 'checked', 'value' => true),
                    )
                ),
            ),
            'show_progress' => array(
                '#type' => 'checkbox',
                '#title' => __('Show progress', 'sabai'),
                '#description' => __('Check this option to show the progress of the process. This may not work with some servers.', 'sabai'),
                '#default_value' => false,
                '#horizontal' => true,
            ),
        );
    }
    
    public function _submitFormForStepImport(Sabai_Context $context, Sabai_Addon_Form_Form $form)
    {
        @set_time_limit(0);
        if ($this->_flush = $context->getRequest()->isAjax() && $context->getRequest()->asBool('show_progress', false)) {
            while(@ob_end_clean());
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            header('Content-type: application/json; charset=utf-8');
            ob_start();
        }
        
        $csv_files = $form->storage['values']['upload']['file'];
        $delimiter = $form->storage['values']['upload']['delimiter'];
        $enclosure = $form->storage['values']['upload']['enclosure'];
        $csv_columns = $this->_getCsvRows($csv_files[0], $delimiter, $enclosure);
        $csv_columns = $csv_columns[0];
        $mapped_fields = $form->storage['values']['map_fields']['fields'];
        $importer_settings = (array)@$form->storage['values']['importer_settings']['settings'];
        if ($test = !empty($form->values['test'])) {
            $test_num = $form->values['test_num'];
        }

        $_mapped_fields = array();
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $importers_by_field_type = $this->CSV_Importers(true);
        $total = $rows_imported = $rows_updated = 0;
        $rows_failed = array();
        $previous_file = null;
        foreach ($csv_files as $csv_file) {
            $row_number = 0;
            $file = new SplFileObject($csv_file['saved_file_path']);
            $file->setFlags(SplFileObject::READ_CSV);
            $file->setCsvControl($delimiter, $enclosure);
            foreach ($file as $csv_row) {
                if (!is_array($csv_row)
                    || array(null) === $csv_row // skip invalid/empty rows
                ) continue;
                
                ++$row_number;
                
                if ($row_number === 1) continue; // skip header row
                
                ++$total;
                
                if ($test && $test_num && $total > $test_num) break 2;
                                
                $values = array();
                foreach ($csv_columns as $column_index => $column_name) {
                    if (!isset($csv_row[$column_index])
                        || !strlen($csv_row[$column_index])
                    ) continue; // no valid value for this row column

                    if (!isset($_mapped_fields[$column_name])) {
                        if (!isset($mapped_fields[$column_name])
                            || (!$_mapped_fields[$column_name] = explode('__', $mapped_fields[$column_name]))
                        ) {
                            // Unset column since mapped field is invalid, to stop further processing the column
                            unset($csv_columns[$column_index], $_mapped_fields[$column_name]);
                            continue;
                        }
                    }
            
                    // Check importer and field
                    $field_name = $_mapped_fields[$column_name][0];    
                    if ((!$field = @$fields[$field_name])
                        || (!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                        || (!$importer = $this->CSV_Importers_impl($importer_name, true))
                    ) {
                        // Unset column since mapped field is invalid, to stop further processing the column
                        unset($csv_columns[$column_index], $_mapped_fields[$column_name]);
                        continue;
                    }
                
                    $column = (string)@$_mapped_fields[$column_name][1];
                    
                    // Init importer settings
                    if (strlen($column)) {
                        $settings = isset($importer_settings[$field_name][$column]) ? $importer_settings[$field_name][$column] : array();
                    } else {
                        $settings = isset($importer_settings[$field_name]) ? $importer_settings[$field_name] : array();
                    }
                    
                    // Import
                    try {                
                        $field_value = $importer->csvImporterDoImport($field, $settings, $column, $csv_row[$column_index]);
                    } catch (Exception $e) {
                        $rows_failed[$row_number] = $e->getMessage();
                        continue 2; // abort importing the current row
                    }
                    
                    // Skip if no value to import
                    if (null === $field_value || false === $field_value) {
                        continue;
                    }
                
                    if (is_array($field_value) && isset($values[$field_name])) {
                        foreach ($field_value as $field_index => $_field_value) {
                            if (!isset($values[$field_name][$field_index])) {
                                $values[$field_name][$field_index] = $_field_value;
                            } else {
                                $values[$field_name][$field_index] += $_field_value;
                            }
                        }
                    } else {
                        $values[$field_name] = $field_value;
                    }
                }

                try {
                    $id_field_name = strtolower($bundle->entitytype_name . '_' . $this->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetInfo('table_id_key'));
                    if (!empty($values[$id_field_name])
                        && ($entity = $this->Entity_Entity($bundle->entitytype_name, $values[$id_field_name], false))
                        && $entity->getBundleName() === $bundle->name
                    ) {
                        if (!$test) {
                            $entity = $this->Entity_Save($entity, $values);
                        }
                        ++$rows_updated;
                        $ret_msg = __('Update successful', 'sabai');
                        $ret_id = $entity->getId();
                        $ret_title = $entity->getTitle();
                    } else {
                        if (!$test) {
                            $entity = $this->Entity_Save($bundle, $values);
                            $ret_id = $entity->getId();
                            $ret_title = $entity->getTitle();
                        } else {
                            $ret_id = '';
                            $ret_title = $values[$bundle->entitytype_name . '_title'];
                        }
                        ++$rows_imported;
                        $ret_msg = __('Import successful', 'sabai');
                    }
                    $ret_status = 'success';
                    
                    // Notify
                    if (!$test) {
                        $this->Action('csv_import_entity', array($bundle, $entity, $values, $importer_settings));
                    }
                } catch (Exception $e) {
                    $rows_failed[$row_number] = $e->getMessage();
                    $ret_id = '';
                    $ret_title = '';
                    $ret_msg = $e->getMessage();
                    $ret_status = 'danger';
                }
            
                if ($this->_flush) {
                    echo json_encode(array(
                        'row_num' => $row_number - 1,
                        'id' => $ret_id,
                        'title' => Sabai::h($ret_title),
                        'message' => Sabai::h($ret_msg),
                        'status' => $ret_status,
                        'file' => $csv_file['name'],
                        'file_changed' => $row_number === 2
                            && count($csv_files) > 1
                            && $csv_file['saved_file_path'] !== $previous_file,
                        'is_test' => $test,
                    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    ob_flush();
                    flush();
                    sleep(1);
                }
            }
            $previous_file = $csv_file['saved_file_path'];
        }
        
        $form->storage['rows_imported'] = $rows_imported;
        $form->storage['rows_updated'] = $rows_updated;
        $form->storage['rows_failed'] = $rows_failed;
        
        // Cleanup
        foreach (array_keys($_mapped_fields) as $column_name) {            
            $field_name = $_mapped_fields[$column_name][0];
            $column = (string)@$_mapped_fields[$column_name][1];      
            if ((!$field = @$fields[$field_name])
                || (!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                || (!$importer = $this->CSV_Importers_impl($importer_name, true))
            ) {
                continue;
            }
            $settings = isset($importer_settings[$field_name][$column_name]) ? $importer_settings[$field_name][$column_name] : array();
            $importer->csvImporterClean($field, $settings, $column);
        }
    }

    protected function _complete(Sabai_Context $context, array $formStorage)
    {
        $error = array();
        if (!empty($formStorage['rows_failed'])) {
            foreach ($formStorage['rows_failed'] as $row_num => $error_message) {
                $error[] = sprintf(__('CSV data on row number %d could not be imported: %s', 'sabai'), $row_num, $error_message);
            }
        }
        $success = array();
        $success[] = sprintf(_n('%d row imported successfullly.', '%d rows imported successfullly.', $formStorage['rows_imported'], 'sabai'), $formStorage['rows_imported']);
        if (!empty($formStorage['rows_updated'])) {
            $success[] = sprintf(_n('%d row updated successfullly.', '%d rows updated successfullly.', $formStorage['rows_updated'], 'sabai'), $formStorage['rows_updated']);
        }
        if (!$this->_flush) {
            $context->addTemplate('form_results');      
            $context->success = $success; 
            $context->error = $error;
        } else {    
            $context->setSuccess()->setSuccessAttributes(array(
                'row_num' => null,
                'success' => $success,
                'error' => $error,
            ));
        }
        @unlink($formStorage['values']['upload']['file']['saved_file_path']);
    }
}
