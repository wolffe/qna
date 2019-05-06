<?php
class Sabai_Helper_Download extends Sabai_Helper
{
    public function help(Sabai $application, $filePath, $fileType = 'application/octet-stream', $name = null, $timestamp = null, $isImage = false)
    {
        if (!is_resource($filePath)) {
            if (!file_exists($filePath)) {
                throw new Sabai_RuntimeException(sprintf('File %s does not exist.', $filePath));
            }

            if (false === $resource = fopen($filePath, 'rb')) {
                throw new Sabai_RuntimeException(sprintf('Failed opening stream for file %s.', $filePath));
            }
        } else {
            $resource = $filePath;
        }
        
        if (!isset($name)) {
            $name = basename($filePath);
        }
        
        if (!$isImage) {
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }
            header('Cache-Control: must-revalidate');
            header('Content-Disposition: attachment; filename="' . str_replace(array("\r", "\n", '"'), '', $name) . '"');
            header('Content-Description: File Transfer');
        } else {
            $cache_limit = time() + 432000; // 5 days
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $cache_limit));
            header('Cache-Control: must-revalidate, max-age=' . $cache_limit);
            header('Content-Disposition: inline; file_name="' . str_replace(array("\r", "\n", '"'), '', $name) . '"');
        }
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', isset($timestamp) ? $timestamp : time()));
        header('Content-Type: ' . $fileType);

        while(@ob_end_clean());
        while (!feof($resource)) echo fgets($resource, 2048);
        fclose($resource);
    }
}