# Id4me

A PHP library aiming to simplify usage of **id4me** functionalities. 

## Functionalities

 - Discovery
 - Registration
 - Authentication
 - Validation :
    - solely following requirements of ID4Me specifications **4.5.3. ID Token Validation**: 1. to 5. and 9.
 
## Architecture 

Following technical structure and flow are currently implemented: 

![id4Me library process image (Petri-Netz)](https://gitlab.com/ID4me/id4me-rp-client-php/raw/0.1.0/images/architecture.jpg "Id4me technical structure and flow")

To see an example of a client consuming current id4me library run: ```php examples/Example.php```

## Prerequisites

In order to work on current source code make sure you have following softwares installed:

- docker latest version
- docker-compose latest version
- php >= 7.0
- php composer

## How to build

- Build local dependencies with ```composer install```

## How to run unit tests

- run a single test: `<SOURCE_CODE_PATH>/vendor/bin/phpunit tests/<TEST_CLASS>.php`
- run all tests: `<SOURCE_CODE_PATH>/vendor/bin/phpunit --configuration phpunit.xml`

## How to start application

- Just start with ```docker-compose up```

## What are we still working on?

- Logout from identity authority

