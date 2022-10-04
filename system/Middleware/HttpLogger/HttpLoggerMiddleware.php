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
     * @var string $requestBodyHash
     */
    private $requestBodyHash;

    /**
     * @var \HashContext $requestHash
     */
    private $requestHash;

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
        $this->config = $container->get('config.http_logger');
        $this->registry = $container->get('registry');
        $this->streamFactory = $container->get(StreamFactoryInterface::class);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $response = $handler->handle($request);

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

        // if($route->getName() && $config->)

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

        $this->requestHash = hash_init('md5');
        hash_update($this->requestHash, 'request');
        hash_update($this->requestHash, "|{$logOption['dateTime']}");
        hash_update($this->requestHash, "|{$logOption['method']}");
        hash_update($this->requestHash, "|{$logOption['ipAddress']}");
        hash_update($this->requestHash, "|{$logOption['url']}");

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
                case self::BodyIgnored:
                $logOption['body'] = null;
                break;

                case self::BodyToFile:
                $logOption['body'] = $this->writeStreamToFile($this->bodyStream, $this->requestBodyHash);
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

        hash_update($this->requestHash, "|" . json_encode($logOption['headers']));
        hash_update($this->requestHash, "|{$logOption['logContext']}");
        hash_update($this->requestHash, "|" . json_encode($logOption['queryParams']));
        hash_update($this->requestHash, "|" . json_encode($logOption['formData']));
        hash_update($this->requestHash, "|{$logOption['bodySize']}");
        hash_update($this->requestHash, "|{$logOption['bodyContext']}");
        hash_update($this->requestHash, "|{$this->requestBodyHash}");
        hash_update($this->requestHash, "|" . json_encode($logOption['uploadedFiles']));

        $logOption['hash'] = hash_final($this->requestHash);
        $this->registry->set('requestHash', $logOption['hash']);
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
            hash_update($hash, $content, $length);
        }

        $this->requestBodyHash = hash_final($hash);

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