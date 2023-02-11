<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

use Hashids\Hashids;
use SlimEdge\Kernel;

class LogData
{
    /**
     * @var array $data
     */
    private $data = [];

    public function __construct(string $type, array $payload = [])
    {
        $datetime = new \DateTime();
        $this->data['type'] = $type;

        $this->append('timestamp', $datetime->getTimestamp() * 1000 + $datetime->format('v'));
        $this->append('datetime', $datetime->format(\DateTime::RFC3339_EXTENDED));
        $this->appendAll($payload);
    }

    public function append(string $key, $value)
    {
        $this->data[$key] = $value;
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
        $this->data['hash'] = hashids('httpLog')->encode($this->data['timestamp']);
        return $this->data;
    }
}