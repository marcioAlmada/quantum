<?php

namespace Minime\Quantum;

/**
 * A Quantum Object that allows parallel states of data structures
 *
 * @package Annotations
 * @author  Márcio Almada and the Minime Community
 * @license MIT
 */
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
     * @param  string                $state
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
    public function extend($new_state, $base_state)
    {
        $this->validateExtendOrFail($new_state, $base_state);
        $this->states[$new_state] = $this->forkState($this->states[$base_state]);
        $this->pick($new_state);

        return $this;
    }

    protected function forkState($state)
    {
        if (is_object($state)) {
            return clone($state);
        }

        return $state;
    }

    /**
     * Modifies current states through a callback
     * @param  callable              $callback
     * @return Minime\Quantum\Object
     */
    public function interact(callable $callback)
    {
        $callback->__invoke($this->current);

        return $this;
    }

    /**
     * Loop through all states applying a callback
     * @param  callable              $callback
     * @return Minime\Quantum\Object
     */
    public function each(callable $callback)
    {
        array_walk($this->states, function (&$state, $identifier) use ($callback) {
          $callback->__invoke($identifier, $state);
        });

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
     * Checks if a given state already exists
     * @param  string  $state
     * @return boolean
     */
    public function has($state)
    {
        return in_array($state, array_keys($this->states));
    }

    protected function validateExtendOrFail($state, $base)
    {
        if ($this->has($state)) {
            throw new \LogicException('Can not extend to an already existing state.');
        }
        if (!$this->has($base)) {
            throw new \LogicException('Can not extend from an inexistent state.');
        }
    }

    /**
     * Points cursor to a given state
     * @param string $state
     */
    protected function pick($state)
    {
        $this->current = &$this->states[$state];
    }

    /**
     * Creates or overrides a given state
     * @param string $state state identifier
     * @param array  $arguments  arguments necessary to call factory
     */
    protected function initialize($state, $arguments = [])
    {
        $this->states[$state] = call_user_func_array($this->factory, $arguments);
    }
}
