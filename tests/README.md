# Test Environment

## Setup Test Environment

1. Spin up your docker container:
    ```bash
    docker-compose up -d
    ```
2. Open the bash of the php container:
    ```bash
    docker-compose exec php bash
    ```
3. Move to the working directory:
    ```bash
    cd /var/cli
    ```
4. Install the dependencies:
    ```bash
    composer install
    ```

## Run the tests

When all dependencies are installed you can run the tests with the following commands:

### Run with Codeception

```bash
./vendor/bin/codecept run -vvv
```

### Run with PHPUnit

For all tests:

```bash
./vendor/bin/phpunit
```

Or for a specific test directory:

```bash
./vendor/bin/phpunit tests/Unit/Grid
```

Or for a specific test file:

```bash
./vendor/bin/phpunit tests/Unit/Grid/Schema/ColumnTest.php
```

#### Using the tests/phpunit.xml Configuration

To use the test-specific configuration:

```bash
./vendor/bin/phpunit -c tests/phpunit.xml
```

#### Running Tests Without Code Coverage

For faster test execution without code coverage requirements:

```bash
./vendor/bin/phpunit -c tests/phpunit-no-coverage.xml
```

To completely hide warnings and only show test results:

```bash
./vendor/bin/phpunit -c tests/phpunit-no-coverage.xml --no-logging --no-output
```

This configuration:

-   Disables code coverage collection and metadata requirements
-   Ignores risky tests and warnings
-   Suppresses deprecation notices
-   Runs significantly faster than the standard configuration
