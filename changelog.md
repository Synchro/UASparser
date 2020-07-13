# Version 0.9 (July 13th, 2020)
A bit of a clean up to make update bots shut up, though it's a bit academic as the data source this library depends on has dried up. It now requires a minimum of PHP 7.3 to tell you that it won't work... It was fun to clean it up a bit anyway!
* Composer cleanup; fewer deps, drop composer.lock, switch to PSR-4 loading
* Configure phpcs, reformat to PSR-12
* Simplified test setup using PHPUnit 9
* Tests are run on PHP 7.3, 7.4, 8.0

# Version 0.53 (Aug 28th 2013)

* Various cache handling fixes
* More PSR-2 and PHPDoc cleanups
* Made all private properties and methods protected to allow overriding
* Change example script to .phps for security

# Version 0.52 (Jun 4th 2013)
Initial release
