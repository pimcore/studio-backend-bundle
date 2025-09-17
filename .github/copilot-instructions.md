This is a php based project. php 8.3 is the minimum version required to run this project.
php 8.4 features are not used in this project. codeception is used for testing.

## Key Guidelines

-   Do not modify `composer.json` or `composer.lock` files.

## Code Standards

-   Use symfony coding standards.
-   Follow PSR-12 for PHP code style.
-   Use spaces for indentation (4 spaces per indent level).
-   Use camelCase for variable and method names, and PascalCase for class names. Constants should be in uppercase with underscores.
-   Use single quotes for strings unless interpolation is needed.
-   Use `===` for comparisons and avoid using `==` unless necessary.
-   Make new classes final unless they are intended to be extended.
-   Dont add '@param' to methods unless the parameter type is not clear from the method signature.
-   Dont add '@return' to methods unless the return type is not clear from the method signature.
-   Follow the principle of least surprise, meaning that code should be easy to understand and follow common conventions.
-   Follow the principle of return early, meaning that if a function can return early, it should do so to avoid deep nesting.
-   Use dependency injection for classes and services.
-   Use interfaces for classes that are intended to be used as services.
-   Use interfaces for dependency injection to allow for easier testing and mocking.
-   Avoid using static methods and properties unless absolutely necessary.
-   Use contructor injection for dependencies.
-   Avoid using global state and singletons.
-   Use constructer promotion for class properties.
-   Follow the principle of minimal visibility, meaning that class properties and methods should be as private as possible.
-   Use interfaces for classes that are intended to be used as services.
-   Avoid using abstract classes unless absolutely necessary.
-   Add a `@throws` annotation to methods that can throw exceptions, and document the exceptions that can be thrown.
-   Add a new line at the end of each file. Use UTF-8 encoding for files.
-   Use `null` coalescing operator (`??`) for default values when applicable.
-   Use `match` expressions for simple value comparisons.
-   Use `array_map`, `array_filter`, and `array_reduce` for array transformations instead of loops when applicable.
-   Use `declare(strict_types=1);` at the top of each file to enforce strict typing.

## Repository Structure

-   Use the `src/` directory for application code.
-   Use the `tests/Unit` directory for unit tests.
-   Use the `config/` directory for configuration files.
-   Use the `public/` directory for public assets and entry points.
-   Use the `vendor/` directory for third-party dependencies managed by Composer.
-   Use the `translations/` directory for translation files.
-   Use the `doc/` directory for documentation files.

## Testing

-   Use phpunit for testing.
-   Follow the phpunit documentation for writing tests.
-   Write unit tests for new functionality. Use mocks and stubs where applicable.
-   Use CoversClass and UsesClass attributes for test classes.
-   Use `phpunit.xml.dist` for PHPUnit configuration.
-   Run tests using `vendor/bin/phpunit run` command.

## Documentation

-   Use `@deprecated` annotation for deprecated methods or classes.
-   Use `@example` annotation for providing examples of usage.
-   Keep documentation up to date with code changes.
-   Suggest changes to the `doc/` folder when appropriate
