# lnwiki - Lightning Network Wiki Platform

A very rudimentary wiki platform where you pay a small amount via the Bitcoin Lightning Network to make edits.

Try it out
----------

* https://wiki.for-bitcoin,com/

Requirements
------------

* PHP 7
* The backend uses the [lightning-php](https://github.com/thorie7912/lightning-php) library to connect to a full c-lightning node to handle payment processing. So, you will need such a node. If the node is not local, it will use an stunnel connection.
* MariaDB server instance.

Installation
------------

1. Run `composer update` to fetch the vendor dependencies.
1. Modify `config.php` (see `config.php-example`) with your server settings.
1. Modify `public/.htaccess` (see `public/.htaccess-example`) with your server settings.
1. Run `cat sql/db_init.sql | mysql` to create the `lnwiki` database and user.
1. Run `php tools/upgrade-db.php` to create the schema and initial data.

Usage
-----

1. Browse to the home page, and start making edits!
