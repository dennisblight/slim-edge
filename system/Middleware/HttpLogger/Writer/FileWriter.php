<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Writer;

use finfo;
use DateTime;
use Exception;
use SlimEdge\Kernel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FileWriter extends BaseWriter
{
    protected $stream;

    /**
     * @param ServerRequestInterface $request
     */
    public function logRequest(ServerRequestInterface $request): ?string
    {
        $datetime = new DateTime();
        $log = [
            'type'       => 'request',
            'datetime'   => $datetime->format(DateTime::RFC3339_EXTENDED),
            'isFile'     => false,
            'method'     => $request->getMethod(),
            'url'        => (string) $request->getUri(),
            'headers'    => $this->cleanRequestHeaders($request->getHeaders()),
            'ip_address' => get_real_ip_address(),
            'params'   => [
                'get'   => $_GET,
                'post'  => $_POST,
                'files' => $_FILES,
            ],
        ];

        $body = (string) $request->getBody();
        if(mb_strlen($body) > 5e5 || is_binary($body)) {
            $log['isFile'] = true;
            $log['body'] = $this->writeBodyToFile($body);
        }
        else {
            $log['body'] = $body;
        }

        $hash = md5(json_encode($log));
        $log['hash'] = $hash;
        
        if(Kernel::$container->has('registry')) {
            $registry = Kernel::$container->get('registry');
            $registry->set('requestHash', $hash);
        }

        $this->writeLog($log);

        return $hash;
    }

    public function writeLog($log)
    {
        try {
            $logJson = json_encode($log);
            $path = array_item($this->config, 'path', BASEPATH . '/storage/logs/http');
            $path .= '/' . date('Ym');
            if(!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $filePath = sprintf('%s/log_%s.log', $path, date('Y-m-d'));

            $maxSize = array_item($this->config, 'max_file_size', false);
            if($maxSize !== false && file_exists($filePath) && filesize($filePath) > $maxSize) {
                $number = 1;
                do {
                    $filePath = sprintf('%s/log_%s_%s.log', $path, date('Y-m-d'), str_pad_left($number, 4, '0'));
                    $number++;
                }
                while(file_exists($filePath) && filesize($filePath) > $maxSize);
            }

            $stream = fopen($filePath, 'a');
            fwrite($stream, $logJson . ',' . PHP_EOL);
            fclose($stream);
        }
        catch(Exception $ex) {
            return false;
        }

        return true;
    }

    protected function cleanRequestHeaders($headers)
    {
        $ignoredHeaders = array_item($this->config, 'ignore_headers');
        if(is_null($ignoredHeaders)) return $headers;

        $ignoredHeaders = array_map(function($item) {
            return 'HTTP_' . str_replace('-', '_', strtoupper($item));
        }, $ignoredHeaders);

        $headers = array_filter($headers, function($item) use ($ignoredHeaders) {
            return !in_array($item, $ignoredHeaders);
        }, ARRAY_FILTER_USE_KEY);

        return array_map(function($item) {
            return count($item) == 1 ? $item[0] : $item;
        }, $headers);
    }

    protected function cleanResponseHeaders($headers)
    {
        return array_map(function($item) {
            return count($item) == 1 ? $item[0] : $item;
        }, $headers);
    }

    /**
     * @param string $requestResult Related request hash
     * @param ResponseInterface $response
     */
    public function logResponse($requestResult, $response)
    {
        $datetime = new DateTime();
        $log = [
            'type'         => 'response',
            'requestHash'  => $requestResult,
            'datetime'     => $datetime->format(DateTime::RFC3339_EXTENDED),
            'isFile'       => false,
            'statusCode'   => $response->getStatusCode(),
            'reasonPhrase' => $response->getReasonPhrase(),
            'headers'      => $this->cleanResponseHeaders($response->getHeaders()),
        ];

        $body = (string) $response->getBody();
        if(mb_strlen($body) > 5e5 || is_binary($body)) {
            $log['isFile'] = true;
            $log['body'] = $this->writeBodyToFile($body);
        }
        else {
            $log['body'] = $body;
        }

        $log['hash'] = md5(json_encode($log));
        
        $this->writeLog($log);
    }

    /**
     * @param string $string
     */
    protected function writeBodyToFile($string)
    {
        $hash = md5($string);
        $size = mb_strlen($string);

        if($size <= $this->getMaxLength() && !array_item($this->config, 'ignore_file', false)) {
            try {
                $path = array_item($this->config, 'path', BASEPATH . '/storage/logs/http');
                $path .= '/files/' . substr($hash,0 , 2);
                if(!file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                $file = $path . '/' . $hash;
                if(!file_exists($file)) {
                    $stream = fopen($path . '/' . $hash, 'w');
                    fwrite($stream, $string);
                    fclose($stream);
                }
            }
            catch(Exception $ex) { /** Ignored */ }
        }

        $finfo = new finfo(FILEINFO_MIME);
        return [
            'name' => $hash,
            'type' => $finfo->buffer($string),
            'size' => $size,
        ];
    }
}