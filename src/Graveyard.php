<?php

namespace Scheb\Tombstone;

use Scheb\Tombstone\Handler\HandlerInterface;
use Scheb\Tombstone\Tracing\PathNormalizer;
use Scheb\Tombstone\Tracing\TraceProvider;

class Graveyard implements GraveyardInterface
{
    /**
     * @var HandlerInterface[]
     */
    private $handlers;

    /**
     * @var TraceProvider
     */
    private $traceProvider;

    /**
     * @var string
     */
    private $sourceDir;

    public function __construct(array $handlers = [], $sourceDir = null)
    {
        $this->handlers = $handlers;
        $this->traceProvider = new TraceProvider();
        $this->setSourceDir($sourceDir);
    }

    public function setSourceDir($sourceDir): void
    {
        $this->sourceDir = $sourceDir;
    }

    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function tombstone(string $date, ?string $author, ?string $label, array $trace): void
    {
        $trace = $this->traceRelativePath($trace);
        $vampire = Vampire::createFromCall($date, $author, $label, $trace);
        foreach ($this->handlers as $handler) {
            $handler->log($vampire);
        }
    }

    private function traceRelativePath(array $trace): array
    {
        if (!$this->sourceDir) {
            return $trace;
        }

        foreach ($trace as $key => &$frame) {
            if (isset($frame['file'])) {
                $frame['file'] = PathNormalizer::makeRelativeTo($frame['file'], $this->sourceDir);
            }
        }

        return $trace;
    }

    public function flush(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->flush();
        }
    }
}
