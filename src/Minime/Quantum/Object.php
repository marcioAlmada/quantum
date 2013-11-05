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
     * Switch to an existing state or create a new one based on an identifier
     * @param  string $state
     * @return Minime\Quantum\Object
     */
    public function mount($state, $args = [])
    {
        if (!$this->has($state)) {
            $this->initialize($state, $args);
        }
        $this->pick($state);

        return $this;
    }

    /**
     * Create a new state based on a previously mounted one
     * @return Minime\Quantum\Object
     */
    public function extend($state, $base)
    {
        $this->validateExtendOrFail($state, $base);
        $base = $this->states[$base];
        $this->states[$state] = (is_object($base) ? clone($base) : $base);
        $this->pick($state);

        return $this;
    }

    /**
     * Modifies current states trhough a callback
     * @param  callable $callback
     * @return Minime\Quantum\Object
     */
    public function interact(callable $callback)
    {
        $callback->__invoke($this->current);

        return $this;
    }

    /**
     * Loop through all states applying a callback
     * @param  callable $callback
     * @return Minime\Quantum\Object
     */
    public function each(callable $callback)
    {
        foreach ($this->states as $identifier => &$state) {
            $callback->__invoke($identifier, $state);
        }

        return $this;
    }

    /**
     * Returns current state reference
     * @return mixed
     */
    public function &expose()
    {
        if (empty($this->states)) {
            throw new \UnderflowException('There are no states to expose.');
        }

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

    protected function validateExtendOrFail($state, $base)
    {
        $this->validateStateExtendOrFail($state);
        $this->validateBaseExtendOrFail($base);
    }

    protected function validateStateExtendOrFail($state)
    {
        if ( $this->has($state)) {
            throw new \LogicException('Can not extend to an already existing state.');
        }
    }

    protected function validateBaseExtendOrFail($base)
    {
        if (!$this->has($base) ) {
            throw new \LogicException('Can not extend from an unexistant state.');
        }
    }

    /**
     * Points cursor to a given state
     * @param  strin $state
     */
    protected function pick($state)
    {
        $this->current = &$this->states[$state];
    }

    /**
     * Creates or overrides a given state
     * @param  string $state state identifier
     * @param  array  $args  args necessary to execute factory
     */
    protected function initialize($state, $args = [])
    {
        $this->states[$state] = call_user_func_array($this->factory, $args);
    }
}
