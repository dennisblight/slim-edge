<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Writer;

use Exception;

class FileWriter extends BaseWriter
{
    public function writeLog(array $logData)
    {
        try
        {
            $json = json_encode($logData);
            $date = date('Y-m-d');
            $directory = "{$this->config->path}/" . date('Ym');
            if(!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filePath = "{$directory}/log_{$date}.log";
            $maxFileSize = $this->config->maxFileSize;
            $number = 0;
            do {
                $numPad = str_pad_left($number, 4, '0');
                $filePath = "{$directory}/log_{$date}_{$numPad}.log";
            }
            while(!is_null($maxFileSize) && file_exists($filePath) && filesize($filePath) > $maxFileSize && $number++);

            $stream = fopen($filePath, 'a');
            fwrite($stream, $json . ',' . PHP_EOL);
            $lastPosition = ftell($stream);
            fclose($stream);

            $filePath = "{$directory}/log.idx";
            $indexData = pack('PvP', $logData['timestamp'], $number, $lastPosition);
            $stream = fopen($filePath, 'a');
            fwrite($stream, $indexData);
            fclose($stream);

            return true;
        }
        catch(Exception $ex)
        {
            return false;
        }
    }
}
