[![Latest Stable Version](https://poser.pugx.org/alexanderallen/panettone/v)](https://packagist.org/packages/alexanderallen/panettone) [![Latest Unstable Version](https://poser.pugx.org/alexanderallen/panettone/v/unstable)](https://packagist.org/packages/alexanderallen/panettone) [![License](https://poser.pugx.org/alexanderallen/panettone/license)](https://packagist.org/packages/alexanderallen/panettone) ![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/AlexanderAllen/panettone/php.yml) ![Coveralls](https://img.shields.io/coverallsCoverage/github/AlexanderAllen/panettone?style=flat&logo=coveralls&link=https%3A%2F%2Fcoveralls.io%2Fgithub%2FAlexanderAllen%2Fpanettone)


# Panettone
A lightweight PHP type generator for Open API (formerly Swagger)

## Testing

Tests are located in the `tests` directory. PHPUnit is installed separately in the `tools/phpunit` directory.

To test, first use Composer to install PHPUnit then run the test script.

    composer -d tools/phpunit install
    . tools/phpunit/test

## Coverage

Coverage details are gathered during testing in Github and [pushed to Coveralls](https://coveralls.io/github/AlexanderAllen/panettone).
