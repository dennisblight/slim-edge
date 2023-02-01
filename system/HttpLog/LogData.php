<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

class LogData
{
    /**
     * @var array $data
     */
    private $data = [];

    /**
     * @var \HashContext $hashContext
     */
    private $hashContext;

    public function __construct(string $type, array $payload = [])
    {
        $this->hashContext = hash_init('md5');
        hash_update($this->hashContext, $type);

        $datetime = new \DateTime();
        $this->data['type'] = $type;

        $this->append('datetime', $datetime->format(\DateTime::RFC3339_EXTENDED));
        $this->append('timestamp', $datetime->getTimestamp() * 1000 + $datetime->format('v'));
        $this->appendAll($payload);
    }

    public function append(string $key, $value, $hash = true)
    {
        $this->data[$key] = $value;
        if($hash === true) {
            $this->updateHash($value);
        }
        elseif(is_string($hash)) {
            $this->updateHash($hash);
        }
    }

    public function appendAll(array $array)
    {
        foreach($array as $key => $value) {
            $this->append($key, $value);
        }
    }

    /**
     * Finish hashing and get data
     * @return array
     */
    public function finish()
    {
        $this->data['hash'] = hash_final($this->hashContext);
        return $this->data;
    }

    private function updateHash($value)
    {
        hash_update($this->hashContext, '|');
        if(is_scalar($value)) {
            hash_update($this->hashContext, strval($value));
        }
        else {
            hash_update($this->hashContext, json_encode($value));
        }
    }
}