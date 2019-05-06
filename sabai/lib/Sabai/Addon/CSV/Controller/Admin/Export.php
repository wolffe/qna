<?php
class Sabai_Addon_CSV_Controller_Admin_Export extends Sabai_Addon_Form_MultiStepController
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
        return array('select_fields', 'exporter_settings', 'export');
    }
    
    public function _getFormForStepSelectFields(Sabai_Context $context, array &$formStorage)
    {
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $options = $custom_field_options = array();
        foreach ($fields as $field_name => $field) {
            if ((!$exporter_name = @$exporters_by_field_type[$field->getFieldType()])
                || (!$exporter = $this->CSV_Exporters_impl($exporter_name, true))
                || !$exporter->csvExporterSupports($bundle, $field)
            ) {
                continue;
            }
            
            $columns = $exporter->csvExporterInfo('columns');
            if (is_array($columns)) {
                if ($field->isCustomField()) {
                    foreach ($columns as $column => $label) {
                        $custom_field_options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            'field' => $this->_getFieldLabel($field) . ' - ' . $label,
                            'column_header' => $field_name . '_' . $column,
                        );
                    }
                } else {
                    foreach ($columns as $column => $column_label) {
                        $options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            'field' => $this->_getFieldLabel($field, $column_label),
                            'column_header' => $field_name . '_' . $column,
                        );
                    }
                }
            } else {
                $option = $this->_getFieldOptionValue($field_name, (string)$columns);
                if ($field->isCustomField()) {
                    $custom_field_options[$option] = array(
                        'field' => $this->_getFieldLabel($field),
                        'column_header' => $field_name,
                    );
                } else {
                    $options[$option] = array(
                        'field' => $this->_getFieldLabel($field),
                        'column_header' => $field_name,
                    );
                }
            }
        }
        uasort($options, create_function('$a, $b', 'return strnatcmp($a["field"], $b["field"]);'));
        if (!empty($custom_field_options)) {
            uasort($custom_field_options, create_function('$a, $b', 'return strnatcmp($a["field"], $b["field"]);'));
            $options += $custom_field_options;
        }
        
        // Disable required fields
        $options_disabled = array();
        foreach ($this->_getAllRequiredFields($context, $bundle) as $field_name) {
            $options_disabled[] = $this->_getFieldOptionValue($field_name);
        }
        
        $form = array(
            '#header' => array(
                '<div class="sabai-alert sabai-alert-info">' . __('Select the fields to export and configure CSV column headers.', 'sabai') . '</div>'
            ),
            'fields' => array(
                '#type' => 'tableselect',
                '#header' => array(
                    'field' => __('Field name', 'sabai'),
                    'column_header' => __('Column header', 'sabai'),
                ),
                '#multiple' => true,
                '#js_select' => true,
                '#options' => $options,
                '#options_disabled' => $options_disabled,
                '#default_value' => array_keys($options),
                '#element_validate' => array(array(array($this, 'validateSelectFields'), array($context, $fields, $bundle))),
            ),
        );
        
        return $form;
    }
    
    protected function _getFieldOptionValue($fieldName, $column = '')
    {
        return $fieldName . '__' . $column;
    }
    
    protected function _getFieldLabel(Sabai_Addon_Field_IField $field, $columnLabel = '')
    {
        $label = Sabai::h($field->getFieldLabel()) . ' (' . $field->getFieldName() . ')';
        if ($field->isCustomField()) {
            $label = '<span class="sabai-label sabai-label-default">' . Sabai::h(__('Custom field', '@@sabai_package_name')) . '</span> ' . $label;
        }
        if (strlen($columnLabel)) {
            $label .=  ' - ' . Sabai::h($columnLabel);
        }
        return $label;
    }
    
    protected function _getAllRequiredFields(Sabai_Context $context, $bundle)
    {
        $required_fields = array();;
        
        if (!empty($bundle->info['parent'])) {
            $required_fields[] = $bundle->entitytype_name . '_parent';
        }
        
        // Always require ID field
        $id_field = $bundle->entitytype_name . '_id';
        if (!in_array($id_field, $required_fields)) {
            $required_fields[] = $id_field;
        }
        
        return $required_fields;
    }
    
    public function validateSelectFields($form, &$value, $element, $context, $fields, $bundle)
    {
        $value = array_filter($value);        

        // Make sure required fields are going to be imported
        foreach ($this->_getAllRequiredFields($context, $bundle) as $field_name) {
            if (isset($fields[$field_name])
                && !in_array($option_value = $this->_getFieldOptionValue($field_name), $value)
            ) {
                $value[] = $option_value;
            }
        }
    }
    
    public function _getFormForStepExporterSettings(Sabai_Context $context, array &$formStorage)
    {
        $form = array('#header' => array(), 'settings' => array());

        $selected_fields = $formStorage['values']['select_fields']['fields'];
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        foreach ($selected_fields as $selected_field) {
            if (!$_selected_field = explode('__', $selected_field)) continue;

            $field_name = $_selected_field[0];
            $column = $_selected_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                 
            $exporter_name = $exporters_by_field_type[$field->getFieldType()];
            if (!$exporter = $this->CSV_Exporters_impl($exporter_name, true)) {
                continue;
            }
            $info = $exporter->csvExporterInfo();
            $parents = array('settings', $field_name);
            if (strlen($column)) {
                $parents[] = $column;
            }
            if ($column_settings_form = $exporter->csvExporterSettingsForm($field, (array)@$info['default_settings'], $column, '"', $parents)) {
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
                $form['settings'][$field_name]['#title_no_escape'] = true;
                if (!isset($form['settings'][$field_name]['#title'])) {
                    $form['settings'][$field_name]['#title'] = $this->_getFieldLabel($field);
                }
            }
        }
        if (empty($form['settings'])) {
            return $this->_skipStepAndGetForm($context, $formStorage);
        }
        
        $form['settings']['#tree'] = true;
        $form['#header'][] = '<div class="sabai-alert sabai-alert-info">' . Sabai::h(__('Please configure additional options for each field.', 'sabai')) . '</div>';
        
        return $form;
    }

    
    public function _getFormForStepExport(Sabai_Context $context, array &$formStorage)
    {
        $context->addTemplate($this->getPlatform()->getAssetsDir('sabai') . '/templates/csv_form');
        $context->results_header = __('Export Results', 'sabai');
        $context->download_url = $this->Url($this->_getBundle($context)->getAdminPath() . '/export/download');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnReadyState = 'function (result, target, trigger, count) {
    var results = target.find(".sabai-csv-results"), table = results.find("table");
    if (!result.row_num) {
        SABAI.ajaxLoader(null, true, table);
        if (result.success) {
            SABAI.flash(result.success, "success", 0);
        }
        if (result.error) {
            SABAI.flash(result.error, "danger", 0);
        }
        if (result.download_file) {
            target.find(".sabai-csv-download").show().on("click", function(e){
                e.preventDefault();
                var href = $(this).attr("href");
                window.location = href + (href.indexOf("?") === -1 ? "?" : "&") + "file=" + result.download_file;
            });
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
    $("<tr class=\'sabai-" + result.status + "\'><th>" + result.row_num + "</th><td>" + result.id + "</td><td>" + (result.title.length ? result.title : "' . Sabai::h(__('(no title)', 'sabai')) . '") + "</td><td>" + result.message + "</td></tr>")
        .appendTo(table.find("tbody"));
    if (count % 5 === 0) {
        SABAI.scrollTo(results.find(".sabai-csv-results-footer"));
    }
}';
        $this->_submitButtons[] = array('#value' => __('Export Now', 'sabai'), '#btn_type' => 'primary', '#btn_size' => 'lg');
        
        return array(
            '#header' => array('<div class="sabai-alert sabai-alert-info">' . Sabai::h(__('Press the export button below to start exporting.', 'sabai')) . '</div>'),
            'filename' => array(
                '#title' => __('File name', 'sabai'),
                '#type' => 'textfield',
                '#field_suffix' => '.csv',
                '#default_value' => $this->_getBundle($context)->name . '-' . date('Ymd', time()),
                '#regex' => '/^[a-zA-Z0-9-_]+$/',
                '#required' => true,
                '#horizontal' => true,
            ),
            'show_progress' => array(
                '#type' => 'checkbox',
                '#title' => __('Show progress', 'sabai'),
                '#description' => __('Check this option to show the progress of the process. This may not work with some servers.', 'sabai'),
                '#default_value' => false,
                '#horizontal' => true,
            ),
            'limit' => array(
                '#type' => 'number',
                '#title' => __('Limit to X records (0 for all records)', 'sabai'),
                '#default_value' => 0,
                '#min_value' => 0,
                '#integer' => true,
                '#horizontal' => true,
                '#size' => 5,
            ),
            'offset' => array(
                '#type' => 'number',
                '#title' => __('Start from Xth record', 'sabai'),
                '#default_value' => 1,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
                '#size' => 5,
            ),
        );
    }
    
    public function _submitFormForStepExport(Sabai_Context $context, Sabai_Addon_Form_Form $form)
    {
        try {
            $this->ValidateDirectory($this->getAddon('CSV')->getExportDir(), true);
        } catch (Exception $e) {
            throw new Sabai_RuntimeException($e->getMessage());
        }
        
        @set_time_limit(0);
        $db = $this->getDB();
        if ($db instanceof SabaiFramework_DB_MySQL) {
            try {
                $db->exec('SET SESSION wait_timeout = 600');
            } catch (SabaiFramework_DB_QueryException $e) {
                $this->LogError($e);
            }
        }
        if ($this->_flush = $context->getRequest()->isAjax() && !empty($form->values['show_progress'])) {
            while(@ob_end_clean());
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            header('Content-type: application/json; charset=utf-8');
            ob_start();
        }
        
        $file = rtrim($this->getAddon('CSV')->getExportDir(), '/') . '/' . $form->values['filename'] . '.csv';         
        if (false === $fp = fopen($file, 'w+')) {
            throw new Sabai_RuntimeException(sprintf('Failed opening file %s with write permission', $file));
        }

        $selected_fields = $form->storage['values']['select_fields']['fields'];
        $exporter_settings = (array)@$form->storage['values']['exporter_settings']['settings'];
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $export_fields = $columns = array();
        foreach ($selected_fields as $selected_field) {
            if (!$_selected_field = explode('__', $selected_field)) continue;

            $field_name = $_selected_field[0];
            $column = $_selected_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                    
            $exporter_name = $exporters_by_field_type[$field->getFieldType()];
            if (!$exporter = $this->CSV_Exporters_impl($exporter_name, true)) {
                continue;
            }
            
            if (strlen($column)) {
                $columns[$field_name][$column] = $column;
            }
            $export_fields[$field_name] = $exporter_name;
        }
        unset($selected_fields);
        
        $headers = array();
        foreach (array_keys($export_fields) as $field_name) {
            if (isset($columns[$field_name])) {
                foreach ($columns[$field_name] as $column) {
                    $headers[] = $field_name . '__' . $column;
                }
            } else {
                $headers[] = $field_name;
            }
        }
        if (false === fputcsv($fp, $headers)) {
            throw new Sabai_RuntimeException(sprintf('Failed writing CSV headers into file %s', $file));
        }
        
        $offset = (int)$form->values['offset'];
        $limit = (int)$form->values['limit'];
        $fetch_limit = $limit && $limit < 100 ? $limit : 100;
        $rows_exported = $rows_failed = $last_id = 0;
        $bundle = $this->_getBundle($context);
        $count = $this->_getQuery($context, $bundle)->count();
        $files = array($file);
        $id_property = $bundle->entitytype_name === 'taxonomy' ? 'term_id' : 'post_id';
        do {
            $i = 0; 
            if ($offset > 1) {
                $entities = $this->_getQuery($context, $bundle)
                    ->sortByProperty($id_property)
                    ->fetch($fetch_limit, $offset - 1);
                $offset = 0;
            } else {
                $entities = $this->_getQuery($context, $bundle)
                    ->propertyIsGreaterThan($id_property, $last_id)
                    ->sortByProperty($id_property)
                    ->fetch($fetch_limit);
            }

            
            
            // Notify
            $this->Action('csv_export_entities', array($bundle, $entities, $export_fields, $exporter_settings));
            
            foreach ($entities as $entity) {
                --$count;
                ++$i;
                $last_id = $entity->getId();
                $row = array();
                $field_values = $entity->getFieldValues(true);
                foreach ($export_fields as $field_name => $exporter_name) {
                    if (!isset($field_values[$field_name])) {
                        // No field value, so populate columns with empty values
                        if (isset($columns[$field_name])) {
                            foreach ($columns[$field_name] as $column) {
                                $row[] = '';
                            }
                        } else {
                            $row[] = '';
                        }
                        continue;
                    }
                
                    $settings = isset($exporter_settings[$field_name]) ? $exporter_settings[$field_name] : array();
                    $settings += array(
                        '_file' => $form->values['filename'],
                    );
                    $exported = $this->CSV_Exporters_impl($exporter_name)->csvExporterDoExport(
                        $fields[$field_name],
                        $settings,
                        $field_values[$field_name],
                        isset($columns[$field_name]) ? $columns[$field_name] : array(),
                        $files
                    );
                    if (isset($columns[$field_name])) {
                        foreach ($columns[$field_name] as $column) {
                            $row[] = isset($exported[$column]) ? $exported[$column] : '';
                        }
                    } else {
                        $row[] = $exported;
                    }
                }
                if (false === fputcsv($fp, $row)) {
                    ++$rows_failed;
                    $row_number = '';
                    $ret_msg = __('Failed writing info CSV file', '@@sabai_package_name');
                    $ret_status = 'danger';
                } else {
                    ++$rows_exported;
                    $row_number = $rows_exported;
                    $ret_msg = __('Export successful', '@@sabai_package_name');
                    $ret_status = 'success';
                }
                if ($this->_flush) {
                    echo json_encode(array(
                        'row_num' => $row_number,
                        'id' => $last_id,
                        'title' => Sabai::h($entity->getTitle()),
                        'message' => Sabai::h($ret_msg),
                        'status' => $ret_status,
                        'i' => $i,
                        'count' => $count,
                    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    ob_flush();
                    flush();
                    sleep(1);
                }
                
                if ($limit && $rows_exported + $rows_failed >= $limit) break 2;
            }            
        } while ($count > 0
            && $i > 0 // prevent loop
        );
        
        $form->storage['rows_exported'] = $rows_exported;
        $form->storage['rows_failed'] = $rows_failed;
        $form->storage['files'] = $files;
        
        fclose($fp);
    }

    protected function _complete(Sabai_Context $context, array $formStorage)
    {
        $success = $error = array();
        if (!empty($formStorage['rows_failed'])) {
            $error[] = sprintf(_n('Faield exporting %d row.', 'Failed exporting %d rows.', $formStorage['rows_failed'], 'sabai'), $formStorage['rows_failed']);
        }
        if ($formStorage['rows_exported'] > 0) {
            $success[] = sprintf(_n('%d row exported successfullly.', '%d rows exported successfullly.', $formStorage['rows_exported'], 'sabai'), $formStorage['rows_exported']);
            $download_file = basename($formStorage['files'][0]);
            if (count($formStorage['files']) > 1
                && class_exists('ZipArchive', false)
            ) {
                $zip = new ZipArchive();
                $zip_file = basename($formStorage['files'][0], '.csv') . '.zip';
                if (true !== $result = $zip->open(rtrim(dirname($formStorage['files'][0]), '/') . '/' . $zip_file, ZipArchive::CREATE)) {
                    $error[] = 'Failed creating zip archive. Error: ' . $result;
                } else {
                    foreach ($formStorage['files'] as $file) {
                        $zip->addFile($file, basename($file));
                    }
                    $zip->close();
                    $download_file = $zip_file; // let user download zip file
                }
            }
        }
        
        $attr = array(
            'row_num' => null,
            'success' => $success,
            'error' => $error,
            'download_file' => $download_file,
            'notice' => sprintf(
                '<a class="sabai-csv-download sabai-btn sabai-btn-primary sabai-btn-lg" href="%s">%s</a>',
                $this->Url($this->_getBundle($context)->getAdminPath() . '/export/download', array('file' => $download_file)),
                Sabai::h(__('Download', 'sabai'))
            ),
        );
        if (!$this->_flush) {
            $context->addTemplate('form_results')->setAttributes($attr);
        } else {   
            $context->setSuccess()->setSuccessAttributes($attr);
        }
    }
    
    protected function _getQuery(Sabai_Context $context, Sabai_Addon_Entity_Model_Bundle $bundle)
    {
        $property = $bundle->entitytype_name === 'taxonomy' ? 'term_entity_bundle_name' : 'post_entity_bundle_name';
        return $this->Filter(
            'csv_export_query',
            $this->Entity_Query($bundle->entitytype_name)->propertyIs($property, $bundle->name),
            array($bundle)
        );
    }
}
