Minime \ Quantum
================

[![Build Status](https://travis-ci.org/marcioAlmada/annotations.png?branch=master)](https://travis-ci.org/marcioAlmada/quantum)
[![Coverage Status](https://coveralls.io/repos/marcioAlmada/quantum/badge.png)](https://coveralls.io/r/marcioAlmada/quantum)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/marcioAlmada/quantum/badges/quality-score.png?s=536d1003a7020d4c172976bff5233171c40f279f)](https://scrutinizer-ci.com/g/marcioAlmada/quantum/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1723710f-a54a-4f9e-94a8-d7f6ddb5faf1/mini.png)](https://insight.sensiolabs.com/projects/1723710f-a54a-4f9e-94a8-d7f6ddb5faf1)

Quantum is unique kind of container made to manage parallel states of data structures. Give it a callable factory and  start unfolding.

## Installation

Friends of terminal: `composer require minime/quantum:~0.0` :8ball:

## Usage

```php
<?php

// Quantum Object looks more expressive when aliased
use Minime\Quantum\Object as Container;

// Quantum needs a callable to produce new states, so let's create one
$Container = (new Container(function(){ return new SomeFancyContainer(); }))

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

// get test container
$TestContainer  = $Container->mount('test')->expose();

// get development container
$DevelopmentContainer = $Container->mount('development')->expose();

// get production container
$ProductionContainer = $Container->mount('production')->expose();

```

Interact with all states at once:

```php
<?php

$Container->each(function($container){
    // routine
});

```

## Copyright

Copyright (c) 2013 Márcio Almada. Distributed under the terms of an MIT-style license. See LICENSE for details.

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/marcioAlmada/quantum/trend.png)](https://bitdeli.com/free "Bitdeli Badge")