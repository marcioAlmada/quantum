<?php

namespace Minime\Quantum;

class Object
{

    /**
     * Callable responsible to produce new states of the object
     * @var callable
     */
    protected $factory;

    /**
     * States of the object
     * @var array
     */
    protected $states = [];

    /**
     * References the current state  of the object
     * @var mixed
     */
    protected $current = null;

    /**
     * Initializes a new Quantum\Object
     * @param callable $factory callable responsible to produce new states of the object
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Switch to an existing state or create a new one based on a identifier
     * @param  string $state
     * @return mixed
     */
    public function mount($state, $args = [])
    {
        if (!$this->has($state)) { $this->initialize($state, $args); }
        $this->pick($state);

        return $this;
    }

    /**
     * Modifies current states trhough a callback
     * @param  callable $callback
     * @return self
     */
    public function interact(callable $callback)
    {
        $callback->__invoke($this->current);

        return $this;
    }

    /**
     * Loop through all states applying a callback
     * @param  callable $callback
     * @return self
     */
    public function each(callable $callback)
    {
        foreach ($this->states as $identifier => &$state) { $callback->__invoke($identifier, $state); }

        return $this;
    }

    /**
     * Returns current state reference
     * @return mixed
     */
    public function &detach()
    {
        if (empty($this->states)) { throw new \UnderflowException('There are no states to detach.'); }

        return $this->current;
    }

    /**
     * List all states available to mount
     * @return array
     */
    public function states()
    {
       return array_keys($this->states);
    }

    /**
     * Checks if a given state already exists
     * @param  string  $state
     * @return boolean
     */
    public function has($state)
    {
        return in_array($state, $this->states());
    }

    /**
     * Points cursor to a given state
     * @param  strin $state
     * @return void
     */
    protected function pick($state)
    {
        $this->current = &$this->states[$state];
    }

    /**
     * Creates or overrides a given state
     * @param  string $state state identifier
     * @param  array  $args  args necessary to execute factory
     * @return void
     */
    protected function initialize($state, $args = [])
    {
        $this->states[$state] = call_user_func_array($this->factory, $args);
    }
}
