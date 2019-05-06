<?php
class Sabai_Addon_CSV_Importer_SocialImporter extends Sabai_Addon_CSV_AbstractImporter
{
    protected function _csvImporterInfo()
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