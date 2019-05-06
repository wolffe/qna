<?php
class Sabai_Addon_CSV_Exporter_ContentExporter extends Sabai_Addon_CSV_AbstractExporter
{
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'content_featured':
                $columns = array(
                    'value' => __('Priority', '@@sabai_package_name'),
                    'featured_at' => __('Featured Date', '@@sabai_package_name'),
                    'expires_at' => __('End Date', '@@sabai_package_name'),
                );
                break;
            case 'content_activity':
                $columns = array(
                    'active_at' => __('Last Active Date', '@@sabai_package_name'),
                    'edited_at' => __('Edited Date', '@@sabai_package_name'),
                );
                break;
            case 'content_guest_author':
                $columns = array(
                    'name' => __('Name', '@@sabai_package_name'),
                    'email' => __('E-mail Address', '@@sabai_package_name'),
                    'url' => __('Website URL', '@@sabai_package_name'),
                    'guid' => __('GUID', '@@sabai_package_name'),
                );
                break;            
            default:
                $columns = null;
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }
    
    public function csvExporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        switch ($this->_name) {
            case 'content_post_views':
                return empty($bundle->info['parent']) && (!isset($bundle->info['public']) || $bundle->info['public'] !== false);
            case 'content_post_slug':
                return empty($bundle->info['parent']);
        }
        return true;
    }
    
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        switch ($this->_name) {
            case 'content_post_user_id':
                return $this->_getUserSettingsForm();
            case 'content_post_published':
            case 'content_activity':
                return $this->_getDateFormatSettingsForm($parents, $settings);
            case 'content_featured':
                if (in_array($column, array('featured_at', 'expires_at'))) {
                    return $this->_getDateFormatSettingsForm($parents, $settings);
                }
                return;
            case 'content_children_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $title = __('Content type/count separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                        '#size' => 5,
                    ),
                ) + $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $title));
            case 'content_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent content ID type', 'sabai'),
                        '#description' => __('Select the type of data used to specify parent content items.', 'sabai'),
                        '#options' => array(
                            'id' => __('ID', 'sabai'),
                            'slug' => __('Slug', 'sabai'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
        }
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        switch ($this->_name) {
            case 'content_post_user_id':
                if ($settings['id_format'] === 'username') {
                    return is_object($value) || ($value = $this->_application->UserIdentity($value)) ? $value->username : null;
                }
                return is_object($value) ? $value->id : $value;
            case 'content_featured':
                $ret = array();
                foreach ($columns as $column) {
                    if (in_array($column, array('featured_at', 'expires_at'))
                        && $settings[$column]['date_format'] === 'string'
                    ) {
                        if (!empty($value[0][$column])
                            && false !== ($_value = @date($settings[$column]['date_format_php'], $value[0][$column]))
                        ) {
                            $ret[$column] = $_value;
                        }
                    } else {
                        $ret[$column] = $value[0][$column];
                    }
                }
                return $ret;
            case 'content_guest_author':
                return parent::csvExporterDoExport($field, $settings, $value, $columns, $files);
            case 'content_activity':
                $ret = array();
                foreach ($columns as $column) {
                    if ($settings[$column]['date_format'] === 'string') {
                        if (!empty($value[0][$column])
                            && false !== ($_value = @date($settings[$column]['date_format_php'], $value[0][$column]))
                        ) {
                            $ret[$column] = $_value;
                        }
                    } else {
                        $ret[$column] = $value[0][$column];
                    }
                }
                return $ret;
            case 'content_parent':
                return $settings['type'] === 'slug' ? $value[0]->getSlug() : $value[0]->getId();
            case 'content_reference':
                return $value[0]->getId();
            case 'content_post_user_id':
                return is_object($value) ? $value->id : null;
            case 'content_post_published':
                if ($settings['date_format'] === 'string') {
                    $ret = @date($settings['date_format_php'], $value);
                    return false !== $ret ? $ret : '';
                }
                return $value;
            case 'content_children_count':
                foreach ($value[0] as $child_bundle_name => $count) {
                    $ret[] = $child_bundle_name . $settings['separator'] . $count;
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            default:
                return $value;
        }
    }
}