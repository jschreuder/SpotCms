SpotApi
=======

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

When you go into the `Spot\Cms\
