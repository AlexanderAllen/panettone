# Contributing

To contribute I recommend that you check out the project directly from Git. 
The Composer package downloaded from Packagist does not come from the `.vscode` directory and the utilities that
make my testing life easier.

PHPUnit, PHPCS, and PHPStan are all used, but they are each in a separate `tools` directory. This keeps the main
`composer.json` free of extraneous dependencies, and prevents Composer dependency conflicts between the various tools.

The downside obviously is that each tool needs to be installed separately.

## Installation

    # Install main project
    git clone git@github.com:AlexanderAllen/panettone.git panettone-dev
    cd panettone-dev
    composer install

    # Install test dependencies
    cd tools/phpunit && composer install

If you are using Visual Studio Code you caan press <kbd>Ctrl</kbd> + <kbd>shift</kbd> + <kbd>P</kbd> to open the command palette.

The task `[ALL] PHPUnit` will run all unit tests.

The task `Local: PHPUnit` accepts an argument, and will run only tests tagged with the string pased in the argument. For example "target".

## Testing

Tests are located in the `tests` directory. PHPUnit is installed separately in the `tools/phpunit` directory.

To test, first use Composer to install PHPUnit then run the test script.

    composer -d tools/phpunit install
    . tools/phpunit/test

## Coverage

Coverage details are gathered during testing in Github and [pushed to Coveralls](https://coveralls.io/github/AlexanderAllen/panettone).
