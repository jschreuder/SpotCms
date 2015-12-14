SpotApi
=======

.. image:: https://secure.travis-ci.org/jschreuder/SpotApi.png
   :target: http://travis-ci.org/jschreuder/SpotApi
   :alt: Build status
.. image:: https://codeclimate.com/github/jschreuder/SpotApi/badges/gpa.svg
   :target: https://codeclimate.com/github/jschreuder/SpotApi
   :alt: Code Climate

At the moment this is very much just a proof-of-concept. There are many tasks
for which a literal MVC implementation isn't ideal in my opinion. The 3 main
ingredients are still very much present here though: the domain layer (Model),
the presentational layer (View) and application control (Controller) are still
very much present.

But instead of modeling any of these directly as classes, I tried to model each
more conceptually and acknowledge the fact that PHP applications are basically
HTTP request handlers.

The Application
---------------

When you go into the `Spot\Api\Application` namespace you'll see the execution
runs through 3 stages:

**1. HTTP Request mapping (routing & request validation)**

In the first stage the HTTP Request object (a PSR-7 ServerRequest) is mapped to
a `Spot\Api\Application\Request\RequestInterface` implementation. At this stage
the first bit of routing is being done by choosing the Request's name. This is
also where basic input validation should be done: does the request adhere to
the way you are expecting the request data to be given, and any filtering and
casts should also be done here. This will allow stage 2 to have a lot cleaner
code.

**2. Request Executor (controller)**

This is where the bulk of the work should be done. Domain-manipulation is only
allowed in here, and should never be done in either the first or third stage.
If you've done validated the input already in the first stage, you can start
working with the input data immediately without any further checks.

The result should be a `Spot\Api\Application\Response\ResponseInterface`
implementation with data necessary for output. It should not do any formatting
of that data, or retrieve things that are necessary for output but not for
executing the request.

**3. Response Generator (view)**

This is where we generate the output. Any related information necessary can be
retrieved, database calls may be done. The basics are provided by the Response
object, what is done here is deciding on how to represent it in a PSR-7
Response message.

This is probably often a JSON or XML object as that is what this is meant to
enable, but HTML is a distinct possibility as well.

RequestInterface & ResponseInterface messages
---------------------------------------------

The Application expects a HTTP request to be mapped to a
`Spot\Api\Application\Request\RequestInterface` instance, which is executed to
result in a `Spot\Api\Application\Response\ResponseInterface` which in turn
will be used to generate a HTTP response. These messages consist of at least a
name, a content-type and attributes. They also implement the `ArrayAccess`
interface to allow direct access to their attributes.

More to come...
---------------

[...]

License
-------

All code is licensed under the MIT license below, unless noted otherwise within
the file. Only free licenses similar to MIT are used.

    Copyright (c) 2015 Jelmer Schreuder

    Permission is hereby granted, free of charge, to any person obtaining a
    copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
    DEALINGS IN THE SOFTWARE.
