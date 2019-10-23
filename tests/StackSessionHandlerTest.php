<?php

namespace Sebdesign\StackSession\Tests;

use Illuminate\Session\ExistenceAwareInterface;
use Illuminate\Session\NullSessionHandler;
use Orchestra\Testbench\TestCase;
use Sebdesign\StackSession\StackSessionHandler;
use Sebdesign\StackSession\StackSessionServiceProvider;
use SessionHandlerInterface;

class StackSessionHandlerTest extends TestCase
{
    /** @test */
    public function it_implements_the_handler_interface()
    {
        $this->assertInstanceOf(SessionHandlerInterface::class, new StackSessionHandler([]));
    }

    /** @test */
    public function it_opens_the_session()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $this->assertTrue($stack->open('foo', 'bar'), 'Failed asserting that the stack handler is open.');
    }

    /** @test */
    public function it_closes_the_session()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $this->assertTrue($stack->close(), 'Failed asserting that the stack handler is closed.');
    }

    /** @test */
    public function it_writes_session_data()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $stack->write('foo', 'bar');

        $this->assertEquals('bar', $a->read('foo'), 'Failed asserting that the data was written to the first handler.');
        $this->assertEquals('bar', $b->read('foo'), 'Failed asserting that the data was written to the second handler.');
    }

    /** @test */
    public function it_reads_session_data()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $this->assertEquals('', $stack->read('foo'), 'Failed asserting that the data from empty handlers is empty.');

        $b->write('foo', 'bar');
        $this->assertEquals('bar', $stack->read('foo'), 'Failed asserting that the data from the second handler is read.');

        $a->write('foo', 'baz');
        $this->assertEquals('baz', $stack->read('foo'), 'Failed asserting that the data from the first handler is read.');
    }

    /** @test */
    public function it_destroys_session_data()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $stack->write('foo', 'bar');
        $stack->destroy('foo');

        $this->assertEquals('', $a->read('foo'), 'Failed asserting that the data was destroyed from the first handler.');
        $this->assertEquals('', $b->read('foo'), 'Failed asserting that the data was destroyed from the second handler.');
    }

    /** @test */
    public function it_cleans_old_session_data()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $stack->write('foo', 'bar');
        $stack->gc(42);

        $this->assertEquals(42, $a->gc, 'Failed asserting that the old data was destroyed from the first handler.');
        $this->assertEquals(42, $b->gc, 'Failed asserting that the old data was destroyed from the second handler.');
    }

    /** @test */
    public function it_sets_the_existence_state()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $stack = new StackSessionHandler(['a' => $a, 'b' => $b]);

        $stack->setExists(true);

        $this->assertTrue($a->exists, 'Failed asserting that the first handler exists.');
        $this->assertTrue($b->exists, 'Failed asserting that the second handler exists.');
    }

    /** @test */
    public function it_extends_the_session_manager()
    {
        $store = $this->app->make('session')->driver('stack');

        $this->assertInstanceOf(StackSessionHandler::class, $store->getHandler());
    }

    /** @test */
    public function it_resolves_the_handlers_from_the_container()
    {
        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $this->instance('session.handlers', ['a' => $a, 'b' => $b]);

        $stack = $this->app->make('session')->driver('stack')->getHandler();

        $stack->write('foo', 'bar');

        $this->assertEquals('bar', $a->read('foo'));
        $this->assertEquals('bar', $b->read('foo'));
    }

    /** @test */
    public function the_handlers_are_resolved_from_the_configuration()
    {
        $this->app->make('config')->set('session.drivers', ['a', 'b']);

        $a = new FakeNullSessionHandler();
        $b = new FakeNullSessionHandler();

        $session = $this->app->make('session');
        $session->extend('a', function () use ($a) { return $a; });
        $session->extend('b', function () use ($b) { return $b; });

        $handlers = $this->app->make('session.handlers');

        $this->assertIsIterable($handlers);

        $handlers = iterator_to_array($handlers);
        $this->assertCount(2, $handlers);
        $this->assertSame($a, $handlers['a']);
        $this->assertSame($b, $handlers['b']);
    }

    /** @test */
    public function the_default_handler_is_the_file_driver()
    {
        $handlers = $this->app->make('session.handlers');
        $handlers = iterator_to_array($handlers);
        $this->assertCount(1, $handlers);
        $this->assertArrayHasKey('file', $handlers);

        $this->app->make('config')->set('session.handlers', []);

        $handlers = $this->app->make('session.handlers');
        $handlers = iterator_to_array($handlers);
        $this->assertCount(1, $handlers);
        $this->assertArrayHasKey('file', $handlers);
    }

    protected function getPackageProviders($app)
    {
        return [StackSessionServiceProvider::class];
    }
}

class FakeNullSessionHandler extends NullSessionHandler implements ExistenceAwareInterface
{
    public $gc;
    public $exists = false;
    public $data = [];

    public function read($sessionId)
    {
        return $this->data[$sessionId] ?? '';
    }

    public function write($sessionId, $data)
    {
        $this->data[$sessionId] = $data;

        return true;
    }

    public function destroy($sessionId)
    {
        unset($this->data[$sessionId]);
    }

    public function gc($lifetime)
    {
        $this->gc = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function setExists($value)
    {
        $this->exists = true;

        return $this;
    }
}
