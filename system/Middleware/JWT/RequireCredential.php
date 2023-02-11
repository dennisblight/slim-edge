<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\JWT;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use SlimEdge\Exceptions\JWTException;
use SlimEdge\Libraries\JWT;

class RequireCredential implements MiddlewareInterface
{
    /**
     * @var Collection $config
     */
    private $config;

    /**
     * @var JWT $jwtEncoder
     */
    private $jwtEncoder;

    /**
     * @var string $token
     */
    protected $token;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config.jwt');
        $this->jwtEncoder = $container->get(JWT::class);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $token = $this->fetchToken($request);

        if(empty($token)) {
            throw new HttpUnauthorizedException($request);
        }
        
        try {
            $credential = $this->jwtEncoder->decode($token);

            if($class = $this->config->get('credentialClass', false)) {
                $credential = new $class($credential, true);
            }

            $request = $request
                ->withAttribute('credential', $credential)
                ->withAttribute('token', $token);

            return $handler->handle($request);
        }
        catch(\UnexpectedValueException $ex) {
            throw new JWTException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    protected function fetchToken(ServerRequestInterface $request): ?string
    {
        if(isset($this->token)) return $this->token;

        $tokenField = $this->config->get('field', 'token');
        $fetchFrom = (array) $this->config->get('source', 'header');

        foreach($fetchFrom as $source) {
            if($source == 'header') {
                $val = $request->getHeaderLine($tokenField);
                if(isset($val))
                    return $this->token = $val;
            }
            elseif($source == 'get' || $source == 'query') {
                $queries = $request->getQueryParams();
                if(isset($queries[$tokenField]))
                    return $this->token = $queries[$tokenField];
            }
            elseif($source == 'post' || $source == 'body') {
                $body = $request->getParsedBody();
                if(isset($body[$tokenField]))
                    return $this->token = $body[$tokenField];
            }
        }
    }
}