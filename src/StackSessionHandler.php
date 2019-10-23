<?php

namespace Sebdesign\StackSession;

use Closure;
use Illuminate\Session\ExistenceAwareInterface;
use SessionHandlerInterface;

class StackSessionHandler implements
    ExistenceAwareInterface,
    SessionHandlerInterface
{
    /**
     * The handler instances.
     *
     * @var iterable<string,\SessionHandlerInterface>
     */
    protected $handlers;

    /**
     * Create a new stack handler instance.
     *
     * @param iterable<string,\SessionHandlerInterface>  $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        $this->apply(static function (
            SessionHandlerInterface $handler
        ) use ($savePath, $sessionName): void {
            $handler->open($savePath, $sessionName);
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->apply(static function (SessionHandlerInterface $handler): void {
            $handler->close();
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        foreach ($this->handlers as $handler) {
            $data = $handler->read($sessionId);

            if ($data !== '') {
                return $data;
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->apply(static function (
            SessionHandlerInterface $handler
        ) use ($sessionId, $data): void {
            $handler->write($sessionId, $data);
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->apply(static function (
            SessionHandlerInterface $handler
        ) use ($sessionId): void {
            $handler->destroy($sessionId);
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        $this->apply(static function (
            SessionHandlerInterface $handler
        ) use ($maxlifetime): void {
            $handler->gc($maxlifetime);
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setExists($value)
    {
        $this->apply(static function (
            SessionHandlerInterface $handler
        ) use ($value): void {
            if ($handler instanceof ExistenceAwareInterface) {
                $handler->setExists($value);
            }
        });

        return $this;
    }

    protected function apply(Closure $callback): void
    {
        foreach ($this->handlers as $handler) {
            $callback($handler);
        }
    }
}
