SpotApi
=======

[![Build Status](https://secure.travis-ci.org/jschreuder/SpotApi.png)](http://travis-ci.org/jschreuder/SpotApi)
[![Code Climate](https://codeclimate.com/github/jschreuder/SpotApi/badges/gpa.svg)](https://codeclimate.com/github/jschreuder/SpotApi)

At the moment this is very much just a proof-of-concept. There are many tasks
for which a literal MVC implementation isn't ideal in my opinion. The 3 main
ingredients are still very much present here though: the data layer (Model),
the presentational layer (View) and application control (Controller) are still
very much present.

But instead of modeling any of these directly as classes, I tried to model each
more conceptually and acknowledge the fact that PHP applications are basically
HTTP request handlers.

The Application
---------------

When you go into the `Spot\Api\Application` namespace you'll see the execution
runs through 3 stages:

**1. HTTP Request mapping**

In the first stage the HTTP Request object (a PSR-7 ServerRequest) is mapped to
a `Spot\Api\Application\Request\RequestInterface` implementation. At this stage
the first bit of routing is being done by choosing the Request's name. This is
also where basic input validation should be done: does the request adhere to
the way you are expecting the request data to be given, and any filtering and
casts should also be done here. This will allow stage 2 to have a lot cleaner
code.

**2. Request execution**

This is where the bulk of the work should be done. Data-modifications are only
allowed in here, and should never be done in either the first or third stage.
If you've done your work already in the first stage, you can start working with
the input data immediately without any further checks.

The result should be a `Spot\Api\Application\Response\ResponseInterface`
implementation with data necessary for output. It should not do any formatting
of that data, or retrieve things that are necessary for output but not for
executing the request.

**3. Response generation**

This is where we generate the output. Any related information can be retrieved,
database calls may be done. The basics are provided by the Response object,
what is done here is deciding on how to represent it in a PSR-7 Response
message.

This is probably often a JSON or XML object as that is what this is meant to
enable, but HTML is a distinct possibility as well.

Basically stage 1 is an added stage, similar to routing but it does more. Stage
2 is the Controller layer and stage 3 is the View layer. The Model layer is not
represented here, but you domain logic may be used at any point.

TODO
----

More to come...

