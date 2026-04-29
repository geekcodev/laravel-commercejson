# Contributing to Laravel CommerceJSON

Thank you for considering contributing to Laravel CommerceJSON!

## Code of Conduct

Please be respectful and constructive in your interactions.

## How to Contribute

### Reporting Bugs

1. Check existing issues to avoid duplicates
2. Use the bug report template
3. Include steps to reproduce
4. Provide expected vs actual behavior

### Suggesting Features

1. Check existing feature requests
2. Use the feature request template
3. Explain the use case
4. Provide examples

### Pull Requests

1. Fork the repository
2. Create a feature branch:
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. Make your changes
4. Run tests:
   ```bash
   composer test
   ```
5. Run code style checker:
   ```bash
   composer format
   ```
6. Commit changes:
   ```bash
   git commit -m "Add amazing feature"
   ```
7. Push to branch:
   ```bash
   git push origin feature/amazing-feature
   ```
8. Open a Pull Request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/laravel-commercejson.git

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Format code
composer format
```

## Testing

```bash
# All tests
composer test

# With coverage
composer test:coverage

# Specific test
php vendor/bin/phpunit tests/Unit/Http/CommerceJsonConnectorTest.php
```

## Code Style

```bash
# Check code style
composer format:test

# Fix code style
composer format
```

## Static Analysis

```bash
# Run PHPStan
composer analyse
```

## Documentation

- Update README.md for new features
- Add PHPDoc comments
- Update CHANGELOG.md

## Questions?

Open an issue or contact the maintainers.
