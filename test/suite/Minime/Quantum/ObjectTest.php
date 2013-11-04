<?php

namespace Minime\Quantum;

class ObjectTest extends \PHPUnit_Framework_TestCase
{

    protected $Quantum;

    public function setUp()
    {
        $this->QuantumObject = new Object(function(){ return new \stdClass; });
    }

    public function tearDown()
    {
        $this->QuantumObject = null;
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error
     */
    public function factoryMustBeCallable()
    {
        new Object([]);
    }

    /**
     * @test
     * @expectedException \UnderflowException
     */
    public function detachFailsWhenStatesAreEmpty()
    {
        $this->QuantumObject->detach();
    }

    /**
     * @test
     */
    public function exposeCreatesParallelInstances()
    {
        $alpha = $this->QuantumObject->expose('alpha')->detach();
        $beta  = $this->QuantumObject->expose('beta')->detach();
        $this->assertNotSame($alpha, $beta);
    }

    /**
     * @test
     */
    public function interactAffectsCurrentStateOnly()
    {
        $alpha =
            $this->QuantumObject
                ->expose('alpha')
                    ->interact(
                        function($instance){
                            $instance->foo = 'bar';
                        }
                    )
                ->detach()
        ;

        $beta =
            $this->QuantumObject
                ->expose('beta')
                    ->interact(
                        function($instance){
                            $instance->foo = 'baz';
                        }
                    )
                ->detach()
        ;

        $this->assertNotSame(json_encode($alpha), json_encode($beta));
    }

    /**
     * @test
     */
    public function eachAffectsStateByReference()
    {
        $this->QuantumObject
            ->expose('alpha')
                ->interact(
                    function($instance){
                        $instance->foo = 'bar';
                    }
                )
            ->expose('beta')
                ->interact(
                    function($instance){
                        $instance->foo = 'baz';
                    }
                )
            ->each(function($identifier, $state){
                $state->property = 'value';
            })
        ;

        $alpha = $this->QuantumObject->expose('alpha')->detach();
        $beta  = $this->QuantumObject->expose('beta')->detach();

        $this->assertSame($alpha->property, $beta->property);
    }

    /**
     * @test
     */
    public function eachPreservesCurrentStatePointer()
    {
        $this->QuantumObject
            ->expose('alpha')
                ->interact(function ($letter){
                    $letter->position = 'first';
                }) 
            ->expose('beta')
                ->interact(function ($letter){
                    $letter->position = 'second';
                }) 
            ->expose('gama')
                ->interact(function ($letter){
                    $letter->position = 'third';
                }) 
            ->expose('beta') # back to beta
            ->each(function($identifier, $state){}) # looping trough states (doing nothing)
        ;
        $this->assertEquals('second', $this->QuantumObject->detach()->position);
    }

    /**
     * @test
     */
    public function eachCantTaintStatesIdentifiers()
    {
        $this->QuantumObject->expose('alpha')->expose('beta')->expose('gama')->each(function($identifiers, $state){
            $identifiers = 'delta';
        });

        $this->assertSame(['alpha', 'beta', 'gama'], $this->QuantumObject->states());
    }

    /**
     * @test
     */
    public function detachReturnsReferences()
    {
        $alpha = $this->QuantumObject->expose('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->expose('alpha')->detach());

        $alpha->position = 'first';
        $this->assertSame($alpha->position, $this->QuantumObject->expose('alpha')->detach()->position);

        $this->QuantumObject = new Object(function(){ return []; });

        $alpha = &$this->QuantumObject->expose('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->expose('alpha')->detach());

        $alpha['position'] = 'first';
        $this->assertSame($alpha['position'], $this->QuantumObject->expose('alpha')->detach()['position']);

        $this->QuantumObject = new Object(function(){ return ''; });

        $alpha = &$this->QuantumObject->expose('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->expose('alpha')->detach());

        $alpha = 'first';
        $this->assertSame($alpha, $this->QuantumObject->expose('alpha')->detach());
    }

    /**
     * @test
     */
    public function factoryCanHaveArguments()
    {
        $this->QuantumObject = new Object(function($x, $y){
            $point = new \stdClass;
            $point->x = $x;
            $point->y = $y;
            return $point;
        });

        $this->QuantumObject->expose('A', [.1, .1]);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error
     */
    public function badFactoryCall()
    {
        $this->QuantumObject = new Object(function($range){
            return range(0, $range);
        });

        $this->QuantumObject->expose('range_a', []);
    }
}