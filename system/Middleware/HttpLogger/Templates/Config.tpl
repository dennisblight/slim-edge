<?php 

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

class CompiledConfig extends Config
{
    public $maxFileSize = '{maxFileSize}';

    public $logErrors = '{logErrors}';

    public $path = '{path}';

    public $writer = '{writer}';

    public $routes = '{routes}';

    public function __construct()
    {
        $this->logRequest = new Config\Request();

        $this->logRequest->maxBody = '{request.maxBody}';

        $this->logRequest->ignoreOnMax = '{request.ignoreOnMax}';

        $this->logRequest->logQuery = '{request.logQuery}';

        $this->logRequest->logFormData = '{request.logFormData}';

        $this->logRequest->logBody = '{request.logBody}';

        $this->logRequest->logUploadedFiles = '{request.logUploadedFiles}';

        $this->logRequest->methods = '{request.methods}';

        $this->logRequest->ignoreMethods = '{request.ignoreMethods}';

        $this->logRequest->headers = '{request.headers}';

        $this->logRequest->ignoreHeaders = '{request.ignoreHeaders}';

        $this->logRequest->routes = '{request.routes}';

        $this->logRequest->ignoreRoutes = '{request.ignoreRoutes}';

        $this->logResponse = new Config\Response();

        $this->logResponse->maxBody = '{response.maxBody}';

        $this->logResponse->ignoreOnMax = '{response.ignoreOnMax}';

        $this->logResponse->logBody = '{response.logBody}';

        $this->logResponse->statusCodes = '{response.statusCodes}';

        $this->logResponse->ignoreStatusCodes = '{response.ignoreStatusCodes}';

        $this->logResponse->headers = '{response.headers}';

        $this->logResponse->ignoreHeaders = '{response.ignoreHeaders}';

        $this->logResponse->routes = '{response.routes}';

        $this->logResponse->ignoreRoutes = '{response.ignoreRoutes}';
    }
}
