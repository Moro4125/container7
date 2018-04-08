<?php

interface ServiceInterface {
}

class Service implements ServiceInterface
{
    public $value;

    function __construct($a = null)
    {
    }
}

class ServiceAlpha extends Service
{
}

class ServiceBeta extends Service
{
}

class Decorator extends Service {

}

class SomeProvider
{
}