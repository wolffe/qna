<?php
class Sabai_Addon_CSV_Controller_Admin_DownloadExported extends Sabai_Controller
{
    protected function _doExecute(Sabai_Context $context, $unlink = true)
    {
        if (!$file = $context->getRequest()->asStr('file')) {
            $context->setBadRequestError();
            return;
        }
        
        $export_dir = $this->getAddon('CSV')->getExportDir();
        if ((!$file_path = realpath($export_dir . '/' . $file))
            || strpos($file_path, $export_dir) !== 0 // must be under the export directory
            || !file_exists($file_path)
        ) {
            $context->setError('Invalid file.');
            return;
        }
        
        $this->Download($file_path);
        if ($unlink) @unlink($file_path);
        exit;
    }
}