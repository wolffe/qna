<?php
class Sabai_Addon_Questions_CSV_Exporter extends Sabai_Addon_CSV_AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'questions_resolved':
                $columns = array(
                    'value' => __('Resolved/Unresolved', 'sabai-discuss'),
                    'resolved_at' => __('Resolved Date', 'sabai-discuss'),
                );
                break;
            case 'questions_closed':
                $columns = array(
                    'value' => __('Closed', 'sabai-discuss'),
                    'closed_at' => __('Closed Date', 'sabai-discuss'),
                    'closed_by' => __('Closed By', 'sabai-discuss'),
                );
                break;
            case 'questions_answer_accepted':
                $columns = array(
                    'score' => __('Score', 'sabai-discuss'),
                    'accepted_at' => __('Accepted Date', 'sabai-discuss'),
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
    
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        switch ($this->_name) {
            case 'questions_resolved':
                switch ($column) {
                    case 'resolved_at':
                        return $this->_getDateFormatSettingsForm($parents);
                }
            case 'questions_closed':
                switch ($column) {
                    case 'closed_at':
                        return $this->_getDateFormatSettingsForm($parents);
                }
            case 'questions_answer_accepted':
                switch ($column) {
                    case 'accepted_at':
                        return $this->_getDateFormatSettingsForm($parents);
                }
        }
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        switch ($this->_name) {
            case 'questions_resolved':
                $ret = parent::csvExporterDoExport($field, $settings, $value, $columns, $files);
                if ($settings['resolved_at']['date_format'] === 'string'
                    && false !== ($date = @date($settings['resolved_at']['date_format_php'], $ret['resolved_at']))
                ) {
                    $ret['resolved_at'] = $date;
                }
                return $ret;
            case 'questions_closed':
                $ret = parent::csvExporterDoExport($field, $settings, $value, $columns, $files);
                if ($settings['closed_at']['date_format'] === 'string'
                    && false !== ($date = @date($settings['closed_at']['date_format_php'], $ret['closed_at']))
                ) {
                    $ret['closed_at'] = $date;
                }
                return $ret;
            case 'questions_answer_accepted':
                $ret = parent::csvExporterDoExport($field, $settings, $value, $columns, $files);
                if ($settings['accepted_at']['date_format'] === 'string'
                    && false !== ($date = @date($settings['accepted_at']['date_format_php'], $ret['accepted_at']))
                ) {
                    $ret['accepted_at'] = $date;
                }
                return $ret;
            default:
                return parent::csvExporterDoExport($field, $settings, $value, $columns, $files);
        }
    }
}