# Some Doctrine 2 Extensions

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine 2 and handle the
records being flushed in the behavioral way. List of extensions:

- Tree - this extension automates the tree handling process and adds some tree specific functions on repository.
- Translatable - gives you a very handy solution for translating records into diferent languages. Easy to setup, easier to use.
- Sluggable - urlizes your specified fields into single unique slug
- Timestampable - updates date fields on create, update and even property change.
- Loggable - helps tracking changes and history of objects, also supports version managment.

Currently these extensions support **Yaml** and **Annotation** mapping. Additional mapping drivers
can be easy implemented using Mapping extension to handle the additional metadata mapping.

## Important

Recently where was a change for type hinting on object manager and other. These changes
**requires doctrine2 from master branch**. Example:

    git clone git://github.com/doctrine/doctrine2.git myproject/library/doctrine
    git clone git://github.com/doctrine/common.git myproject/library/doctrine-common

To autoload these libraries use same class loader from doctrine common package for instance.
    
If you are using windows, there is **msysgit** tool available.

### Latest updates

**2011-03-05**

- Merged Boussekeyt Jules pull request for Loggable extension, tweeked to support versioning
- Added typehints for object manager and classmetadata in all extensions

### ODM MongoDB support

There is a plan to port all extensions for different object manager support and now
half of extensions can be used with ODM also.

- Translatable
- Sluggable
- Timestampable
- Loggable

Are allready ported to support ODM MongoDB

All these extensions can be nested together. And most allready use only annotations without interface requirement
to not to aggregate the entity itself and has implemented proper caching for metadata.

There is a post introducing to these extensions on [doctrine project](http://www.doctrine-project.org/blog/doctrine2-behavioral-extensions "Doctrine2 behavior extensions")

You can test these extensions on [my blog](http://gediminasm.org/test/ "Test doctrine behavior extensions").

All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") also.

### Running the tests:

PHPUnit 3.4 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- run: **git submodule init**
- run: **git submodule update**
- go to tests directory: **cd tests**
- run **cp phpunit.dist.xml phpunit.xml**
- run: **phpunit**
- optional - run mongodb in background to complete all tests 

### Contributors:

- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Christophe Coevoet [stof](http://github.com/stof)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- Klein Florian [docteurklein](http://github.com/docteurklein)
