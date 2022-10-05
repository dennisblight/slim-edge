<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

use Exception;
use Slim\Routing\RouteContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SlimEdge\Entity\Collection;
use SlimEdge\Paths;

use function SlimEdge\Helpers\enable_cache;
use function SlimEdge\Helpers\uuid_format;

class HttpLoggerMiddleware implements MiddlewareInterface
{
    public const ContextNone = 0;

    public const ContextQuery = 1;

    public const ContextFormData = 2;

    public const ContextBody = 4;

    public const ContextUploadedFiles = 8;

    public const BodyContent = 0;

    public const BodyIgnored = 1;

    public const BodyToFile = 2;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var Writer\BaseWriter $writer
     */
    private $writer;

    /**
     * @var Collection $registry
     */
    private $registry;

    public function __construct(ContainerInterface $container)
    {
        $this->registry = $container->get('registry');
        $this->initConfig($container);
    }

    private function initConfig(ContainerInterface $container)
    {
        $cacheEnabled = enable_cache('config');
        if($cacheEnabled && file_exists($path = Paths::Cache . '/httpLogger/CompiledConfig.php')) {
            require $path;
            $this->config = new CompiledConfig;
            return;
        }

        $config = $container->get('config.http_logger');
        $this->config = new Config($config);

        if($cacheEnabled) {
            $this->config->compileConfig($path);
        }
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $this->logRequest($request);
        try
        {
            $response = $handler->handle($request);
            $this->logResponse($request, $response);
            return $response;
        }
        catch(Exception $ex)
        {
            $this->logError($ex);
            throw $ex;
        }
    }

    private function logError(Exception $ex)
    {
        $writer = $this->getWriter();
        if(!$writer || !$this->config->logErrors) return;

        $logData = new LogData('error', [
            'requestHash'  => $this->registry->get('requestHash'),
            'errorClass'   => get_class($ex),
            'errorCode'    => $ex->getCode(),
            'errorMessage' => $ex->getMessage(),
            'errorFile'    => $ex->getFile(),
            'errorLine'    => $ex->getLine(),
        ]);

        $logOption = $logData->finish();
        $this->registry->set('errorHash', $logOption['hash']);

        $writer->writeLog($logOption);
    }

    private function logRequest(ServerRequestInterface $request)
    {
        $writer = $this->getWriter();
        if(!$writer) return;

        $config = $this->config->logRequest;

        $route = RouteContext::fromRequest($request)->getRoute();
        if(!$config->checkRoute($route)) {
            return;
        }

        $routeConfig = $this->config->getConfigForRoute($route, 'logRequest');
        if($routeConfig) {
            $config->override($routeConfig);
        }

        if(!$config->checkMethod($request->getMethod())) {
            return;
        }

        $streamAnalyzer = $this->analyzeStream($request->getBody());

        $logData = new LogData('request', [
            'method'    => $request->getMethod(),
            'ipAddress' => get_real_ip_address(),
            'url'       => (string) $request->getUri(),
            'headers'   => $config->filterHeaders($request->getHeaders()),
            'bodySize'  => $streamAnalyzer->size,
        ]);

        $bodyContext = self::BodyContent;
        $logContext = self::ContextNone;
        $queryParams = null;
        $formData = null;
        $body = null;
        $uploadedFiles = null;

        if($streamAnalyzer->isBinary || ($config->maxBody && $streamAnalyzer->size > $config->maxBody)) {
            if($config->ignoreOnMax) {
                $bodyContext = self::BodyIgnored;
            }
            else {
                $bodyContext = self::BodyToFile;
            }
        }

        if($config->logQuery) {
            $logContext |= self::ContextQuery;
            $queryParams = $request->getQueryParams();
        }

        if($config->logFormData) {
            $logContext |= self::ContextFormData;
            $formData = $request->getParsedBody();
        }

        if($config->logBody) {
            $logContext |= self::ContextBody;
            switch($bodyContext) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $body = $this->storeAnalyzedStream($streamAnalyzer);
                break;

                default:
                $body = (string) $request->getBody();
                break;
            }
        }

        if($config->logUploadedFiles) {
            $logContext |= self::ContextUploadedFiles;
            $uploadedFiles = $this->processUploadedFiles($request->getUploadedFiles());
        }

        $logData->append('bodyContext', $bodyContext);
        $logData->append('logContext', $logContext);
        $logData->append('queryParams', $queryParams);
        $logData->append('formData', $formData);
        $logData->append('body', $body, $streamAnalyzer->hash);
        $logData->append('uploadedFiles', $uploadedFiles);

        $logOption = $logData->finish();
        $this->registry->set('requestHash', $logOption['hash']);
        $writer->writeLog($logOption);
    }

    private function logResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $writer = $this->getWriter();
        if(!$writer) return;

        $config = $this->config->logResponse;

        $route = RouteContext::fromRequest($request)->getRoute();
        if(!$this->config->logRequest->checkRoute($route) || !$config->checkRoute($route)) {
            return;
        }

        $routeConfig = $this->config->getConfigForRoute($route, 'logResponse');
        if($routeConfig) {
            $config->override($routeConfig);
        }

        if(!$config->checkStatusCode($response->getStatusCode())) {
            return;
        }

        $streamAnalyzer = $this->analyzeStream($response->getBody());

        $logData = new LogData('response', [
            'requestHash' => $this->registry->get('requestHash'),
            'headers'     => $config->filterHeaders($response->getHeaders()),
            'bodySize'    => $streamAnalyzer->size,
        ]);

        $bodyContext = self::BodyContent;
        $body = null;

        if($streamAnalyzer->isBinary || ($config->maxBody && $streamAnalyzer->size > $config->maxBody)) {
            if($config->ignoreOnMax) {
                $bodyContext = self::BodyIgnored;
            }
            else {
                $bodyContext = self::BodyToFile;
            }
        }

        if($config->logBody) {
            switch($bodyContext) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $body = $this->storeAnalyzedStream($streamAnalyzer);
                break;

                default:
                $body = (string) $response->getBody();
                break;
            }
        }
        else $bodyContext = self::BodyIgnored;

        $logData->append('bodyContext', $bodyContext);
        $logData->append('body', $body, $streamAnalyzer->hash);

        $logOption = $logData->finish();
        $this->registry->set('responseHash', $logOption['hash']);
        $writer->writeLog($logOption);
    }

    private function processUploadedFiles(array $uploadedFiles)
    {
        $result = [];
        foreach($uploadedFiles as $index => $uploadedFile) {
            if(is_array($uploadedFile)) {
                $result[$index] = $this->processUploadedFiles($uploadedFile);
            }
            else {
                /** @var UploadedFileInterface $uploadedFile */
                $streamAnalyzer = $this->analyzeStream($uploadedFile->getStream());
                $result[$index] = $this->storeAnalyzedStream($streamAnalyzer);
            }
        }
        return $result;
    }

    private function analyzeStream(StreamInterface $stream)
    {
        return (new StreamAnalyzer($stream))->analyze();
    }

    /**
     * Store analyzed stream to new log file as reference
     * @return string File path relative to log path
     */
    private function storeAnalyzedStream(StreamAnalyzer $streamAnalyzer)
    {
        $fileName = uuid_format($streamAnalyzer->hash) . '-' . $streamAnalyzer->size;
        $path = '/files/' . substr($fileName, 0, 2);
        $directory = $this->config->path . $path;
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = "$directory/$fileName";
        if(!file_exists($filePath)) {
            $streamAnalyzer->stream->rewind();
            $stream = fopen($filePath, 'w+');
            while(!$streamAnalyzer->stream->eof()) {
                $content = $streamAnalyzer->stream->read(1024000);
                fwrite($stream, $content);
            }

            fclose($stream);
        }

        return "$path/$fileName";
    }

    /**
     * @return Writer\BaseWriter
     */
    private function getWriter()
    {
        if(is_null($this->writer)) {
            $this->writer = false;
            $writerClass = $this->config->writer;
            if(is_subclass_of($writerClass, Writer\BaseWriter::class)) {
                $this->writer = is_object($writerClass) ? $writerClass : new $writerClass($this->config);
            }
        }

        return $this->writer;
    }
}
