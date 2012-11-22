UASparser
=========

A User Agent String parser for PHP

[![Build Status](https://travis-ci.org/Synchro/UASparser.png)](https://travis-ci.org/Synchro/UASparser)

This is a parser for the user agent strings presented by HTTP clients. This code is based on the libraries available from http://user-agent-string.info

Licensed under the LGPL, see license.txt for details.

This version amended by Marcus Bointon:
- Creates a UAS namespace
- Adds unit tests
- Adds Travis config
- Removes the view source option for security
- Makes the DownloadData function public so it can be done on demand
- Uses the system temp dir for default cache location
- Cleans up phpdocs
- Reformats code in PSR-2 style
- Fixes poor code in the example script