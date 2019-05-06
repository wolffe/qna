<?php
class Sabai_Addon_CSV_Exporter_SocialExporter extends Sabai_Addon_CSV_AbstractExporter
{
    protected function _csvExporterInfo()
    {
        foreach ($this->_application->Social_Medias() as $media_name => $media) {
            $columns[$media_name] = $media['label'];
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }
}