Minime \ Quantum
================

[![Build Status](https://travis-ci.org/marcioAlmada/annotations.png?branch=master)](https://travis-ci.org/marcioAlmada/quantum)
[![Coverage Status](https://coveralls.io/repos/marcioAlmada/quantum/badge.png)](https://coveralls.io/r/marcioAlmada/quantum)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/marcioAlmada/quantum/badges/quality-score.png?s=536d1003a7020d4c172976bff5233171c40f279f)](https://scrutinizer-ci.com/g/marcioAlmada/quantum/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1723710f-a54a-4f9e-94a8-d7f6ddb5faf1/mini.png)](https://insight.sensiolabs.com/projects/1723710f-a54a-4f9e-94a8-d7f6ddb5faf1)

Quantum is unique kind of container made to manage parallel states of data structures. Give it a callable factory and see it unfold.

## Installation

Friends of terminal: `composer require minime/quantum:~0.0` :8ball:

## Usage

```php
<?php

use Minime\Quantum\Object as Environment; // Quantum Object looks more expressive when aliased

// Quantum needs a callable to produce new states, so let's create one
return (new Environment(function(){ return new FancyContainer(); }))

// this is the default environment
->mount('default')->interact(function($container){
    $container->shared('Database', new Database(
        [
            'driver' => 'postgre',
            'host' => 'localhost'
        ]
    ));
    /*...*/
})

// this will be our test/cli environment
->extend('default', 'test')->interact(function($container){
    $container->get('Database')->config(
        [
            'database' => 'app_unit_test',
            'user' => 'foo',
            'password' => 'bar'
        ]
    );
})

// this will be our development environment
->extend('default', 'development')->interact(function($container){
    $container->get('Database')->config(
        [
            'database' => 'app_development',
            'user' => 'bar',
            'password' => 'baz'
        ]
    );
})


// production!
->extend('default', 'production')->interact(function($container){
    $container->get('Database')->config(
        [
            'host' => 'my.production.ip',
            'database' => 'app',
            'user' => 'app',
            'password' => 'P@sW04d'
        ]
    );
});

```

## Switching between states:

```php
<?php

$Environment->mount('test'); // switched to test environment
$TestContainer  = $Environment->expose();

$Environment->mount('development'); // switched to development environment
$DevelopmentContainer = $Environment->expose(); // voilla 

$Environment->mount('production'); // switched to production environment
$ProductionContainer = $Environment->expose(); // voilla

```

Interact with all states at once:

```php
<?php

$Enviroment->each(function($container){
    // routine
});

```

## Copyright

Copyright (c) 2013 Márcio Almada. Distributed under the terms of an MIT-style license. See LICENSE for details.

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/marcioAlmada/quantum/trend.png)](https://bitdeli.com/free "Bitdeli Badge")