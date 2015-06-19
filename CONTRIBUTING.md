# Contributing to Emogrifier

Those that wish to contribute bug fixes, new features, refactorings and
clean-up to Emogrifier are more than welcome.

When you contribute, please take the following things into account:

## Install the development dependencies

To install the development dependencies (PHPUnit and PHP_CodeSniffer), run the
following command:

    composer install


## Unit-test your changes

Please cover all changes with unit tests and make sure that your code does not
break any existing tests.

To run the existing PHPUnit tests, run this command:

    vendor/bin/phpunit Tests/


## Coding Style

Please use the same coding style (PSR-2) as the rest of the code. Indentation
is four spaces.

Please check your code with the provided PHP_CodeSniffer standard:

    vendor/bin/phpcs --standard=Configuration/PhpCodeSniffer/Standards/Emogrifier/ Classes/ Tests/

Please make your code clean, well-readable and easy to understand.

If you add new methods or fields, please add proper PHPDoc for the new
methods/fields. Please use grammatically correct, complete sentences in the
code documentation.


## Git commits

Git commits should have a <= 50 character summary, optionally followed by a
blank line and a more in depth description of 79 characters per line.

[Please squash related commits together](http://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html).

Please use grammatically correct, complete sentences in the commit messages.
