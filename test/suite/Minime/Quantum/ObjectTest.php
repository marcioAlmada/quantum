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
    public function factoryFailsWithWrongCall()
    {
        $this->QuantumObject = new Object(function ($range) {
            return range(0, $range);
        });

        $this->QuantumObject->mount('range_a', []);
    }

    /**
     * @test
     * @expectedException \UnderflowException
     */
    public function exposeMustFailWhenStatesAreEmpty()
    {
        $this->QuantumObject->expose();
    }

    /**
     * @test
     */
    public function exposeReturnsReferences()
    {
        # structures
        $alpha = $this->QuantumObject->mount('alpha')->expose();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->expose());

        $alpha->position = 'first';
        $this->assertSame($alpha->position, $this->QuantumObject->mount('alpha')->expose()->position);

        # arrays
        $this->QuantumObject = new Object(function () { return []; });

        $alpha = &$this->QuantumObject->mount('alpha')->expose();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->expose());

        $alpha['position'] = 'first';
        $this->assertSame($alpha['position'], $this->QuantumObject->mount('alpha')->expose()['position']);

        # primitives
        $this->QuantumObject = new Object(function () { return 'foo'; });

        $alpha = &$this->QuantumObject->mount('alpha')->expose();
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->expose());

        $alpha = 'first';
        $this->assertSame($alpha, $this->QuantumObject->mount('alpha')->expose());
    }

    /**
     * @test
     */
    public function mountMustCreateParallelInstances()
    {
        $alpha = $this->QuantumObject->mount('alpha')->expose();
        $beta  = $this->QuantumObject->mount('beta')->expose();
        $this->assertNotSame($alpha, $beta);
    }

    /**
     * @test
     */
    public function interactOnlyAffectsCurrentState()
    {
        $alpha =
            $this->QuantumObject
                ->mount('alpha')
                    ->interact(
                        function ($instance) {
                            $instance->foo = 'bar';
                        }
                    )
                ->expose()
        ;

        $beta =
            $this->QuantumObject
                ->mount('beta')
                    ->interact(
                        function ($instance) {
                            $instance->foo = 'baz';
                        }
                    )
                ->expose()
        ;

        $this->assertNotSame(json_encode($alpha), json_encode($beta));
    }

    /**
     * @test
     */
    public function eachAffectsStatesByReference()
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

        $alpha = $this->QuantumObject->mount('alpha')->expose();
        $beta  = $this->QuantumObject->mount('beta')->expose();

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
        $this->assertEquals('second', $this->QuantumObject->expose()->position);
    }

    /**
     * @test
     */
    public function eachCanNotAlterStatesIdentifiers()
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
    public function extendShouldForkExistingState()
    {
        $alpha =
            $this->QuantumObject
                ->mount('alpha')
                    ->interact(function ($letter) {
                        $letter->position = 1;
                    })
                ->expose();
        $beta =
            $this->QuantumObject
                ->extend('beta', 'alpha')
                    ->interact(function ($letter) {
                        $letter->position++;
                    })
            ->expose();

        $this->assertSame(1, $alpha->position);
        $this->assertSame(2, $beta->position);
        $this->assertNotSame($alpha, $beta);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @dataProvider badExtendCallDataProvider
     */
    public function extendMustFailWithWrongCall($mounted, $state, $base)
    {
        $this->QuantumObject->mount($mounted)->extend($state, $base);
    }

    public function extendWrongCallDataProvider()
    {
        return [
            ['state_a', 'state_b', 'undefined_state'],
            ['state_a', 'state_a', 'state_b']
        ];
    }
}
