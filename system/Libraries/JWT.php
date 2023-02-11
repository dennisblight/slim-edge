<?php

declare(strict_types=1);

namespace SlimEdge\Libraries;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SlimEdge\Entity\AbstractCollection;
use SlimEdge\Entity\Collection;
use UnexpectedValueException;

class JWT
{
    /**
     * @var Collection $config
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config.jwt');
    }

    public function encode($payload = null)
    {
        $key = $this->getKey('private_key');

        $append = [
            'iat' => time(),
            'iss' => get_base_url(),
        ];

        if($this->config->has('duration')) {
            $append['exp'] = strtotime($this->config->duration);
        }

        $resolvedPayload = $this->resolvePayload($payload);

        $data =  array_merge(
            ['jti' => FirebaseJWT::urlsafeB64Encode(random_bytes(4))],
            $resolvedPayload,
            $append
        );

        $algorithm = $this->config->get('algorithm', 'HS256');
        return FirebaseJWT::encode($data, $key, $algorithm);
    }

    private function resolvePayload($payload)
    {
        if(is_array($payload)) {
            return $payload;
        }
        elseif($payload instanceof AbstractCollection) {
            return $payload->getArrayCopy();
        }
        elseif(is_object($payload)) {
            $resolved = [];
            foreach($payload as $prop => $val) $resolved[$prop] = $val;
            return $resolved;
        }
        elseif(is_null($payload)) {
            return [];
        }
        
        $type = typeof($payload);
        throw new InvalidArgumentException("Could not resolve 'payload' from argument type '{$type}'");
    }

    public function decode($token)
    {
        $key = $this->getKey('public_key');

        if($this->config->has('duration')) {
            FirebaseJWT::$leeway = INF;
        }

        $algorithm = $this->config->get('algorithm', 'HS256');

        if(is_string($key) || is_resource($key)) {
            $key = new Key($key, is_array($algorithm) ? $algorithm[0] : $algorithm);
        }

        return FirebaseJWT::decode($token, $key, (array) $algorithm);
    }

    public function verify($token)
    {
        try {
            $this->decode($token);
            return true;
        }
        catch(SignatureInvalidException $ex) { return false; }
        catch(InvalidArgumentException $ex) { return false; }
        catch(UnexpectedValueException $ex) { return false; }
        catch(BeforeValidException $ex) { return false; }
        catch(ExpiredException $ex) { return false; }
    }

    private function getKey($type)
    {
        $key = $this->config->get($type, $this->config->get('key'));
        if(is_callable($key)) {
            $key = $key();
            $this->config->set($type, $key);
        }

        if(empty($key)) {
            throw new RuntimeException("Unable to fetch '{$type}' or 'key' from configuration file.");
        }

        return $key;
    }
}