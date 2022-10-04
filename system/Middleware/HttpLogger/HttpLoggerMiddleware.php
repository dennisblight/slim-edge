<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

use Slim\Routing\RouteContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SlimEdge\Entity\Collection;

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

    /**
     * @var string $bodyHash
     */
    private $bodyHash;

    /**
     * @var StreamInterface $bodyStream
     */
    private $bodyStream;

    /**
     * @var StreamFactoryInterface $streamFactory
     */
    private $streamFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->registry = $container->get('registry');
        $this->streamFactory = $container->get(StreamFactoryInterface::class);

        $config = $container->get('config.http_logger');
        $this->config = new Config($config);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $this->writeRequest($request);

        $response = $handler->handle($request);

        $this->writeResponse($request, $response);

        // $arguments = $this->getArguments($request);
        // if($arguments['ignoreHttpLog']) {
        //     return $response;
        // }

        // $writerClass = $this->config->writer;
        // if(is_subclass_of($writerClass, Writer\BaseWriter::class)) {
        //     /** @var Writer\BaseWriter $writer */
        //     $writer = is_object($writerClass) ? $writerClass : new $writerClass($this->config);
        //     $result = $writer->logRequest($request);

        //     $writer->logResponse($result, $response);
        // }

        return $response;
    }

    private function writeRequest(ServerRequestInterface $request)
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

        $dateTime = new \DateTime();

        $logOption = [
            'type'          => 'request',
            'datetime'      => $dateTime->format(\DateTime::RFC3339_EXTENDED),
            'method'        => $request->getMethod(),
            'ipAddress'     => get_real_ip_address(),
            'url'           => $request->getUri(),
            'headers'       => $config->filterHeaders($request->getHeaders()),
            'bodySize'      => $this->processBody($request->getBody()),
            'bodyContext'   => self::BodyContent,
            'logContext'    => self::ContextNone,
            'queryParams'   => null,
            'formData'      => null,
            'body'          => null,
            'uploadedFiles' => null,
        ];

        $requestHash = hash_init('md5');
        hash_update($requestHash, 'request');
        hash_update($requestHash, "|{$logOption['datetime']}");
        hash_update($requestHash, "|{$logOption['method']}");
        hash_update($requestHash, "|{$logOption['ipAddress']}");
        hash_update($requestHash, "|{$logOption['url']}");
        hash_update($requestHash, "|" . json_encode($logOption['headers']));

        if($config->maxBody && $logOption['bodySize'] > $config->maxBody) {
            if($config->ignoreOnMax) {
                $logOption['bodyContext'] = self::BodyIgnored;
            }
            else {
                $logOption['bodyContext'] = self::BodyToFile;
            }
        }

        if($config->logQuery) {
            $logOption['logContext'] |= self::ContextQuery;
            $logOption['queryParams'] = $request->getQueryParams();
        }

        if($config->logFormData) {
            $logOption['logContext'] |= self::ContextFormData;
            $logOption['formData'] = $request->getParsedBody();
        }

        if($config->logBody) {
            $logOption['logContext'] |= self::ContextBody;
            switch($logOption['bodyContext']) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $logOption['body'] = $this->writeStreamToFile($this->bodyStream, $this->bodyHash);
                break;

                default:
                $logOption['body'] = $this->getBodyContent();
                break;
            }
        }

        $this->bodyStream->close();

        if($config->logUploadedFiles) {
            $logOption['logContext'] |= self::ContextUploadedFiles;
            $logOption['uploadedFiles'] = $this->processUploadedFiles($request->getUploadedFiles());
        }

        hash_update($requestHash, "|{$logOption['logContext']}");
        hash_update($requestHash, "|" . json_encode($logOption['queryParams']));
        hash_update($requestHash, "|" . json_encode($logOption['formData']));
        hash_update($requestHash, "|{$logOption['bodySize']}");
        hash_update($requestHash, "|{$logOption['bodyContext']}");
        hash_update($requestHash, "|{$this->bodyHash}");
        hash_update($requestHash, "|" . json_encode($logOption['uploadedFiles']));

        $logOption['hash'] = hash_final($requestHash);
        $this->registry->set('requestHash', $logOption['hash']);
    }

    private function writeResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $writer = $this->getWriter();
        if(!$writer) return;

        $config = $this->config->logResponse;

        $route = RouteContext::fromRequest($request)->getRoute();
        if(!$config->checkRoute($route)) {
            return;
        }

        $routeConfig = $this->config->getConfigForRoute($route, 'logResponse');
        if($routeConfig) {
            $config->override($routeConfig);
        }

        if(!$config->checkStatusCode($response->getStatusCode())) {
            return;
        }

        $dateTime = new \DateTime();

        $logOption = [
            'type'          => 'response',
            'datetime'      => $dateTime->format(\DateTime::RFC3339_EXTENDED),
            'requestHash'   => $this->registry->requestHash,
            'headers'       => $config->filterHeaders($response->getHeaders()),
            'bodySize'      => $this->processBody($response->getBody()),
            'bodyContext'   => self::BodyContent,
            'body'          => null,
        ];

        $responseHash = hash_init('md5');
        hash_update($responseHash, 'response');
        hash_update($responseHash, "|{$logOption['datetime']}");
        hash_update($responseHash, "|{$logOption['requestHash']}");
        hash_update($responseHash, "|" . json_encode($logOption['headers']));

        if($config->maxBody && $logOption['bodySize'] > $config->maxBody) {
            if($config->ignoreOnMax) {
                $logOption['bodyContext'] = self::BodyIgnored;
            }
            else {
                $logOption['bodyContext'] = self::BodyToFile;
            }
        }

        if($config->logBody) {
            switch($logOption['bodyContext']) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $logOption['body'] = $this->writeStreamToFile($this->bodyStream, $this->bodyHash);
                break;

                default:
                $logOption['body'] = $this->getBodyContent();
                break;
            }
        }
        else $logOption['bodyContext'] = self::BodyIgnored;

        hash_update($responseHash, "|{$logOption['bodySize']}");
        hash_update($responseHash, "|{$logOption['bodyContext']}");
        hash_update($responseHash, "|{$this->bodyHash}");

        $logOption['hash'] = hash_final($responseHash);
        $this->registry->set('responseHash', $logOption['hash']);
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
                $result[$index] = $this->writeStreamToFile($uploadedFile->getStream());
            }
        }
        return $result;
    }

    private function processBody(StreamInterface $body)
    {
        $body->rewind();
        $resource = fopen('php://temp', 'w+');
        $this->bodyStream = $this->streamFactory->createStreamFromResource($resource);
        $hash = hash_init('md5');
        $size = 0;

        while(!$body->eof()) {
            $content = $body->read(102400);
            $length = strlen($content);
            $size += $length;
            $this->bodyStream->write($content);
            hash_update($hash, $content);
        }

        $this->bodyHash = hash_final($hash);

        return $size;
    }

    private function writeStreamToFile(StreamInterface $sourceStream, ?string $hash = null)
    {
        if(is_null($hash)) {
            $sourceStream->rewind();
            $hashContext = hash_init('md5');
            while(!$sourceStream->eof()) {
                $content = $sourceStream->read(102400);
                hash_update($hashContext, $content);
            }
            $hash = hash_final($hashContext);
        }

        $sourceStream->seek(-1, SEEK_END);
        
        $fileName = uuid_format($hash) . '-' . $sourceStream->tell();
        $path = '/files/' . substr($fileName, 0, 2);
        $directory = $this->config->path . $path;
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = "$directory/$fileName";
        if(!file_exists($filePath)) {
            $sourceStream->rewind();
            $stream = fopen($filePath, 'w+');
            while(!$sourceStream->eof()) {
                $content = $sourceStream->read(1024000);
                fwrite($stream, $content);
            }

            fclose($stream);
        }

        return "$path/$fileName";
    }

    private function getBodyContent()
    {
        return (string) $this->bodyStream;
    }

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