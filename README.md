# UASparser — A User Agent String parser for PHP

** Important: while the code in this package is functional, it's no longer any use because the data source at user-agent-string.info (now udger.com) it relied on is no longer available.**

It may work with udger.com's commercial data sources, but that's not available to test with; I recommend you switch to a different system such as [Mobile-Detect](https://github.com/serbanghita/Mobile-Detect).

[![Build Status](https://travis-ci.org/Synchro/UASparser.png?branch=master)](https://travis-ci.org/Synchro/UASparser)

[![Latest Stable Version](https://poser.pugx.org/Synchro/UASparser/v/stable.png)](https://packagist.org/packages/Synchro/UASparser)
[![Latest Unstable Version](https://poser.pugx.org/Synchro/UASparser/v/unstable.png)](https://packagist.org/packages/Synchro/UASparser)

This is a parser and classifier for user agent strings presented by HTTP clients.

This code is based on the libraries by Jaroslav Mallat available from http://user-agent-string.info/

Licensed under the LGPL, see license.txt for details.

This version improved by [Marcus Bointon](https://github.com/Synchro):

* [Maintained on GitHub](https://github.com/Synchro/UASparser)
* [Published on packagist.org](https://packagist.org/packages/synchro/uasparser)
* "Runs" under PHP 7.3 and up.
* Uses the UAS namespace
* Reformatted code in PSR-12 style
* PSR-4 autoloading

## Documentation

Release notes may be found in the [changelog](changelog.md).

Generate PHPDocs like this:

```
phpdoc --directory UAS --target ./phpdoc --ignore Tests/ --sourcecode --force --title UASParser
```
