<?php

namespace Minime\Quantum;

class ObjectTest extends \PHPUnit_Framework_TestCase
{

    protected $Quantum;

    public function setUp()
    {
        $this->QuantumObject = new Object(function () { return new \stdClass; });
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
        $alpha = $this->QuantumObject->mount('alpha')->detach();
        $beta  = $this->QuantumObject->mount('beta')->detach();
        $this->assertNotSame($alpha, $beta);
    }

    /**
     * @test
     */
    public function interactAffectsCurrentStateOnly()
    {
        $alpha =
            $this->QuantumObject
                ->mount('alpha')
                    ->interact(
                        function ($instance) {
                            $instance->foo = 'bar';
                        }
                    )
                ->detach()
        ;

        $beta =
            $this->QuantumObject
                ->mount('beta')
                    ->interact(
                        function ($instance) {
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
            ->mount('alpha')
                ->interact(
                    function ($instance) {
                        $instance->foo = 'bar';
                    }
                )
            ->mount('beta')
                ->interact(
                    function ($instance) {
                        $instance->foo = 'baz';
                    }
                )
            ->each(function ($identifier, $state) {
                $state->property = 'value';
            })
        ;

        $alpha = $this->QuantumObject->mount('alpha')->detach();
        $beta  = $this->QuantumObject->mount('beta')->detach();

        $this->assertSame($alpha->property, $beta->property);
    }

    /**
     * @test
     */
    public function eachPreservesCurrentStatePointer()
    {
        $this->QuantumObject
            ->mount('alpha')
                ->interact(function ($letter) {
                    $letter->position = 'first';
                })
            ->mount('beta')
                ->interact(function ($letter) {
                    $letter->position = 'second';
                })
            ->mount('gama')
                ->interact(function ($letter) {
                    $letter->position = 'third';
                })
            ->mount('beta') # back to beta
            ->each(function ($identifier, $state) {}) # just looping trough states
        ;
        $this->assertEquals('second', $this->QuantumObject->detach()->position);
    }

    /**
     * @test
     */
    public function eachCantTaintStatesIdentifiers()
    {
        $this->QuantumObject
        ->mount('alpha')
        ->mount('beta')
        ->mount('gama')
        ->each(function ($identifiers, $state) {
                $identifiers = 'delta';
        });

        $this->assertSame(['alpha', 'beta', 'gama'], $this->QuantumObject->states());
    }

    /**
     * @test
     */
    public function detachReturnsReferences()
    {
        # structures
        $alpha = $this->QuantumObject->mount('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->detach());

        $alpha->position = 'first';
        $this->assertSame($alpha->position, $this->QuantumObject->mount('alpha')->detach()->position);

        # arrays
        $this->QuantumObject = new Object(function () { return []; });

        $alpha = &$this->QuantumObject->mount('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->detach());

        $alpha['position'] = 'first';
        $this->assertSame($alpha['position'], $this->QuantumObject->mount('alpha')->detach()['position']);

        # primitives
        $this->QuantumObject = new Object(function () { return 'foo'; });

        $alpha = &$this->QuantumObject->mount('alpha')->detach();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->detach());

        $alpha = 'first';
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->detach());
    }

    /**
     * @test
     */
    public function factoryCanHaveArguments()
    {
        $this->QuantumObject = new Object(function ($x, $y) {
            $point = new \stdClass;
            $point->x = $x;
            $point->y = $y;

            return $point;
        });

        $this->QuantumObject->mount('A', [.1, .1]);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error
     */
    public function badFactoryCall()
    {
        $this->QuantumObject = new Object(function ($range) {
            return range(0, $range);
        });

        $this->QuantumObject->mount('range_a', []);
    }

    /**
     * @test
     */
    public function extend()
    {
        $alpha =
            $this->QuantumObject
                ->mount('alpha')
                    ->interact(function ($letter) {
                        $letter->position = 1;
                    })
                ->detach();
        $beta =
            $this->QuantumObject
                ->extend('beta', 'alpha')
                    ->interact(function ($letter) {
                        $letter->position++;
                    })
            ->detach();

        $this->assertSame(1, $alpha->position);
        $this->assertSame(2, $beta->position);
        $this->assertNotSame($alpha, $beta);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @dataProvider badExtendCallDataProvider
     */
    public function badExtendCall($mounted, $state, $base)
    {
        $this->QuantumObject->mount($mounted)->extend($state, $base);
    }

    public function badExtendCallDataProvider()
    {
        return [
            ['state_a', 'state_b', 'undefined_state'],
            ['state_a', 'state_a', 'state_b']
        ];
    }
}
