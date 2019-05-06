<?php
class Sabai_Addon_Questions_CSV_Importer extends Sabai_Addon_CSV_AbstractImporter
{
    protected function _csvImporterInfo()
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
    
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
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
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        switch ($this->_name) {
            case 'questions_resolved':
                if ($column === 'resolved_at') {
                    if ($settings['date_format'] === 'string'
                        && false === ($value = strtotime($value))
                    ) {
                        return null;
                    }
                }
                return array(array($column => $value));
            case 'questions_closed':
                if ($column === 'closed_at') {
                    if ($settings['date_format'] === 'string'
                        && false === ($value = strtotime($value))
                    ) {
                        return null;
                    }
                }
                return array(array($column => $value));
            case 'questions_answer_accepted':
                if ($column === 'accepted_at') {
                    if ($settings['date_format'] === 'string'
                        && false === ($value = strtotime($value))
                    ) {
                        return null;
                    }
                }
                return array(array($column => $value));
            default:
                return parent::csvImporterDoImport($field, $settings, $column, $value);
        }
    }
}