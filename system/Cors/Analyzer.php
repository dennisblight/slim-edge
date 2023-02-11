<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use Laminas\Diactoros\ServerRequest;
use Slim\Routing\RouteContext;

class Analyzer
{
    private const OriginHeader = 'Origin';

    private const RequestMethodHeader = 'Access-Control-Request-Method';

    private const RequestHeadersHeader = 'Access-Control-Request-Headers';

    private const AllowOriginHeader = 'Access-Control-Allow-Origin';

    private const ExposeHeadersHeader = 'Access-Control-Expose-Headers';

    private const MaxAgeHeader = 'Access-Control-Max-Age';

    private const AllowCredentialsHeader = 'Access-Control-Allow-Credentials';

    private const AllowMethodsHeader = 'Access-Control-Allow-Methods';

    private const AllowHeadersHeader = 'Access-Control-Allow-Headers';

    /** @var ServerRequest $request */
    private $request;

    /** @var Config $config */
    private $config;

    /**
     * @var null|bool $hasCredentials
     */
    private $hasCredentials = null;

    /**
     * @var ?string[] $headers
     */
    private $headers = null;

    /**
     * @var array<string, string> $analyzerResult
     */
    private $analyzerResult = [];

    public function __construct(Config $config, ServerRequest $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @return array<string,string>
     */
    public function analyze(): array
    {
        $isCorsRequest = $this->analyzeOrigins()
            | $this->analyzeRequestMethods()
            | $this->analyzeRequestHeaders()
            | $this->analyzeExposeHeaders();
        
        if($isCorsRequest && !is_null($maxAge = $this->config->maxAge)) {
            $this->analyzerResult[self::MaxAgeHeader] = strval($maxAge);
        }

        return $this->analyzerResult;
    }

    private function analyzeOrigins(): bool
    {
        if($this->request->hasHeader(self::OriginHeader)) {
            $requestOrigin = $this->request->getHeaderLine(self::OriginHeader);
            $origin = (string) $this->request->getUri()->withPath('');
            $allowOrigins = $this->config->allowOrigins;
            if(in_array($origin, $allowOrigins)) {
                $result = $requestOrigin;
            }
            elseif(count($allowOrigins) > 0 && $allowOrigins[0] === '*' && !$this->hasCredentials()) {
                $result = '*';
            }
            else {
                $result = $origin;
            }

            $this->analyzerResult[self::AllowOriginHeader] = $result;
            
            return true;
        }

        return false;
    }

    private function analyzeRequestMethods(): bool
    {
        if($this->request->hasHeader(self::RequestMethodHeader)) {
            $routingResults = RouteContext::fromRequest($this->request)->getRoutingResults();
            $allowedMethods = $routingResults->getAllowedMethods();
            if(in_array('GET', $allowedMethods)) {
                array_push($allowedMethods, 'HEAD');
            }

            $this->analyzerResult[self::AllowMethodsHeader] = $allowedMethods;

            return true;
        }

        return false;
    }

    private function analyzeRequestHeaders(): bool
    {
        if($this->request->hasHeader(self::RequestHeadersHeader)) {
            $requestHeaders = $this->request->getHeaderLine(self::RequestHeadersHeader);
            $requestHeaders = array_map('trim', explode(',', strtolower($requestHeaders)));
            $allowedHeaders = $this->config->allowHeaders;
            $intersectedHeaders = array_intersect($requestHeaders, $allowedHeaders);
            if(count($intersectedHeaders) < count($requestHeaders) && count($allowedHeaders) > 0 && $allowedHeaders[0] === '*') {
                if(!$this->hasCredentials()) {
                    $resolved = '*';
                }
                else {
                    unset($allowedHeaders[0]);
                    $resolved = join(', ', $allowedHeaders);
                }
            }
            else {
                $resolved = join(', ', $intersectedHeaders);
            }

            if($resolved !== '') {
                $this->analyzerResult[self::AllowHeadersHeader] = $resolved;
                return true;
            }
        }

        return false;
    }

    private function analyzeExposeHeaders(): bool
    {
        if($this->request->hasHeader(self::ExposeHeadersHeader)) {
            $exposeHeaders = $this->config->exposeHeaders;
            $corsHeaders = $this->getCorsHeaders();
            $intersectedHeaders = array_intersect($exposeHeaders, $corsHeaders);
            if(count($intersectedHeaders) < count($corsHeaders) && count($exposeHeaders) > 0 && $exposeHeaders[0] === '*') {
                if(!$this->hasCredentials()) {
                    $resolved = '*';
                }
                else {
                    unset($exposeHeaders[0]);
                    $resolved = join(', ', $corsHeaders);
                }
            }
            else {
                $resolved = join(', ', $intersectedHeaders);
            }

            $this->analyzerResult[self::ExposeHeadersHeader] = $resolved;

            return true;
        }
        return false;
    }

    private function hasCredentials(): bool
    {
        if(is_bool($this->hasCredentials))
            return $this->hasCredentials;

        $allowCredentials = array_intersect($this->config->allowCredentials, $this->getCorsHeaders());
        $this->hasCredentials = count($allowCredentials) > 0;

        if($this->hasCredentials) {
            $this->analyzerResult[self::AllowCredentialsHeader] = 'true';
        }

        return $this->hasCredentials;
    }

    /**
     * @return string[]
     */
    private function getCorsHeaders(): array
    {
        if(!is_null($this->headers))
            return $this->headers;
        
        $headers = $this->request->getHeaders();
        if(isset($headers['accept']) && !$this->containsUnsafeByte($headers['accept'])) {
            unset($headers['accept']);
        }

        if(isset($headers['accept-language'])) {
            unset($headers['accept-language']);
        }

        if(isset($headers['content-language']) && !$this->containsUnsafeByte2($headers['content-language'])) {
            unset($headers['conent-language']);
        }

        /** Need reworks */
        if(isset($headers['content-type']) && !$this->containsUnsafeByte($headers['content-type'])) {
            unset($headers['content-type']);
        }

        return $this->headers ?? ($this->headers = array_keys($headers));
    }

    /**
     * @param string[] $values
     */
    private function containsUnsafeByte(array $values): bool
    {
        $unsafeBytes = [0x22, 0x28, 0x29, 0x3A, 0x3C, 0x3E, 0x3F, 0x40, 0x5B, 0x5C, 0x5D, 0x7B, 0x7D, 0x7F];
        foreach($values as $value) {
            for($i = 0; $i < strlen($value); $i++) {
                $hex = ord($value[$i]);
                if($hex < 0x20 && $hex !== 0x09)
                    return true;
                if(in_array($hex, $unsafeBytes))
                    return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $values
     */
    private function containsUnsafeByte2(array $values): bool
    {
        $unsafeBytes = [0x20, 0x2A, 0x2C, 0x2D, 0x2E, 0x3B, 0x3D];
        foreach($values as $value) {
            for($i = 0; $i < strlen($value); $i++) {
                $hex = ord($value[$i]);
                if(0x30 <= $hex && $hex <= 0x39)
                    continue;
                elseif(0x41 <= $hex && $hex <= 0x5A)
                    continue;
                elseif(0x61 <= $hex && $hex <= 0x7A)
                    continue;
                elseif(!in_array($hex, $unsafeBytes))
                    continue;

                return true;
            }
        }

        return false;
    }
}