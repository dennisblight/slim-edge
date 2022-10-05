<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Writer;

class FileWriter extends BaseWriter
{
    public function writeLog(array $logData)
    {
        $json = json_encode($logData);

        $date = date('Y-m-d');
        $directory = "{$this->config->path}/" . date('Ym');
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = "{$directory}/log_{$date}.log";
        $maxFileSize = $this->config->maxFileSize;
        if(!is_null($maxFileSize) && file_exists($filePath) && filesize($filePath) > $maxFileSize) {
            
            $number = 1;
            do {
                $numPad = str_pad_left($number, 4, '0');
                $filePath = "{$directory}/log_{$date}_{$numPad}.log";
                $number++;
            }
            while(file_exists($filePath) && filesize($filePath) > $maxFileSize);
        }

        $stream = fopen($filePath, 'a');
        fwrite($stream, $json . ',' . PHP_EOL);
        fclose($stream);
    }
}
