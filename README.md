sword2-client
=============

This is the repository for the ddi-sword2 service for 
passing a set of defined meta-data and binary file(s) to the
**DSpace SwordV2** staged server implementation. This client
supports the deposit of one item into multiple collections on
submission when our custom ingestor has been configured for use
by the sword-serverv2 service, within the sword-v2-server.cfg
file in dspace/config/modules:

    plugin.named.org.dspace.content.packager.PackageIngester = \
      mx.ecosur.infonomia.dspace.packager.DSpaceMETSIngester = METS

Please see our Ingester project at GitHub for more information
on configuration and usage.

[https://github.com/ecosur-infonomia/dspace-custom-ingesters]
(https://github.com/ecosur-infonomia/dspace-custom-ingesters)

**Build and Test**

This utility uses composer for php dependencies. To install,
please download composer.phar, and install our dependencies
with the following:

    $ php composer.phar install

For more information on composer, please see the [packagist site.](https://packagist.org/)

We use PHPUnit for unit testing. In order to run the unit tests, you'll first need to 
install the dependencies of the project using Composer: php composer.phar install --dev. 

We also use Grunt for running the testing and the build process for this project.

To get started, use npm from the top-level in this directory to install
all node based dependencies:

    $ npm install

Then run grunt

    $ grunt

For more information on npm, please see the [npm site.](https://npmjs.org/)

For more information on Grunt, please see the [grunt site.](http://gruntjs.com/)
