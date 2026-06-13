# Contributing to Laravel Catchy

Thank you for considering contributing to Laravel Catchy! To ensure that the project remains stable, readable, and easy to maintain, please follow these guidelines when contributing.

## Code of Conduct

Please be respectful, helpful, and welcoming to all contributors and community members.

## How Can I Contribute?

### Reporting Bugs
If you find a bug:
1. Search existing issues to ensure it hasn't already been reported.
2. If it hasn't, open a new issue using the **Bug Report** template.
3. Provide as much detail as possible, including:
   - Your PHP and Laravel versions.
   - A clear description of the expected vs. actual behavior.
   - Code snippets, error stack traces, or step-by-step reproduction instructions.

### Suggesting Features
We welcome feature ideas!
1. Check existing issues/discussions to see if the feature has been proposed.
2. If not, open a new issue using the **Feature Request** template.
3. Describe the feature, why it would be useful, and how you envision its API or behavior.

### Submitting Pull Requests (PRs)
1. Fork the repository and create your branch from `main`.
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Run the existing test suite to make sure everything passes:
   ```bash
   ./vendor/bin/phpunit
   ```
4. Write clean, well-documented code adhering to PSR-12 coding standard.
5. Add tests for any new features or bug fixes.
6. Commit your changes with descriptive commit messages.
7. Push to your fork and submit a pull request to the `main` branch.

## Testing

Ensure that your PR does not break any tests. Run the test suite before submitting:

```bash
./vendor/bin/phpunit
```

## License

By contributing to Laravel Catchy, you agree that your contributions will be licensed under its [MIT License](LICENSE).
