<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;
use Psr\Http\Message\StreamInterface;

/**
 * Analyze stream size and hash
 */
final class StreamAnalyzer
{
    /**
     * @var StreamInterface $stream
     */
    public $stream;

    /**
     * @var int $size
     */
    public $size = 0;

    /**
     * @var string $hash
     */
    public $hash = null;

    /**
     * @var bool $isBinary
     */
    public $isBinary = false;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return self
     */
    public function analyze()
    {
        $this->stream->rewind();
        $hash = hash_init('md5');

        while(!$this->stream->eof()) {
            $content = $this->stream->read(102400);
            $this->size += strlen($content);
            $this->isBinary = $this->isBinary || is_binary($content);
            hash_update($hash, $content);
        }

        $this->hash = hash_final($hash);

        return $this;
    }
}