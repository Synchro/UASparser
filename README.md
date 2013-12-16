#UASparser

**A User Agent String parser for PHP**

[![Build Status](https://travis-ci.org/Synchro/UASparser.png?branch=master)](https://travis-ci.org/Synchro/UASparser)
[![Coverage Status](https://coveralls.io/repos/Synchro/UASparser/badge.png?branch=master)](https://coveralls.io/r/Synchro/UASparser?branch=master)
[![Dependency Status](https://www.versioneye.com/php/Synchro:UASparser/dev-master/badge.png)](https://www.versioneye.com/php/Synchro:UASparser/dev-master)
[![Depending Status](http://depending.in/Synchro/UASparser.png)](http://depending.in/Synchro/UASparser)

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Synchro/UASparser/badges/quality-score.png?s=0fb01793b5e8a32d39d659ffa122b1ea0c525b0e)](https://scrutinizer-ci.com/g/Synchro/UASparser/)
[![Code Coverage](https://scrutinizer-ci.com/g/Synchro/UASparser/badges/coverage.png?s=57c1f76304ab8d9a53bed5188a06711db5327fdd)](https://scrutinizer-ci.com/g/Synchro/UASparser/)

[![Latest Stable Version](https://poser.pugx.org/Synchro/UASparser/v/stable.png)](https://packagist.org/packages/Synchro/UASparser)
[![Latest Unstable Version](https://poser.pugx.org/Synchro/UASparser/v/unstable.png)](https://packagist.org/packages/Synchro/UASparser)

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/Synchro/uasparser/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

This is a parser and classifier for user agent strings presented by HTTP clients.

This code is based on the libraries by Jaroslav Mallat available from http://user-agent-string.info/

Licensed under the LGPL, see license.txt for details.

This version improved by [Marcus Bointon](https://github.com/Synchro):

* [Maintained on GitHub](https://github.com/Synchro/UASparser)
* [Published on packagist.org](https://packagist.org/packages/synchro/uasparser)
* Creates a UAS namespace
* Adds unit tests
* Adds Travis config
* Removes the view source option for security
* Makes the downloadData function public so it can be done on demand
* Uses the system temp dir for default cache location
* Cleans up phpdocs
* Reformats code in PSR-2 style
* Fixes poor code in the example script
* Improves error handling and debugging, adds variable timeouts
* Adds support for gzip compression of database downloads
* Adds PSR-0 autoload config

##Documentation

Release notes may be found in the [changelog](changelog.md).

Generate PHPDocs like this:

```
phpdoc --directory UAS --target ./phpdoc --ignore Tests/ --sourcecode --force --title UASParser
```
