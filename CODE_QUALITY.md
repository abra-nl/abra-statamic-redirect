# Code Quality Tools Setup

This package uses a comprehensive suite of code quality tools to ensure high-quality, maintainable code:

- **Laravel Pint**: Code formatting and style fixing
- **PHPStan**: Static analysis for type safety and bug detection
- **Rector**: Automated refactoring and code modernization

## Tools Overview

### 🎨 Laravel Pint
Code formatter based on PHP CS Fixer, preconfigured with Laravel standards.

- **Configuration**: `pint.json`
- **Purpose**: Automatic code formatting and style consistency
- **Rules**: Laravel preset with custom tweaks for package development

### 🔍 PHPStan  
Static analysis tool that finds bugs without running the code.

- **Configuration**: `phpstan.neon`
- **Purpose**: Type checking, dead code detection, undefined method calls
- **Level**: 6 (moderate strictness, good for package development)
- **Focus**: `src/` directory only (excludes tests due to Laravel framework dependencies)

### ⚡ Rector
Automated refactoring tool for instant upgrades and code quality improvements.

- **Configuration**: `rector.php`
- **Purpose**: Code modernization, PHP version upgrades, best practices
- **Rules**: PHP 8.1 features, type declarations, code quality improvements

## Available Commands

### Individual Tool Commands

```bash
# Laravel Pint - Code Formatting
composer pint                # Fix formatting issues
composer pint:check         # Check formatting without fixing
composer pint:dirty         # Format only changed files (git)

# PHPStan - Static Analysis
composer stan               # Run static analysis
composer stan:baseline     # Generate baseline for existing issues

# Rector - Automated Refactoring
composer rector             # Apply refactoring changes
composer rector:dry         # Preview changes without applying
```

### Workflow Commands

```bash
# Check code quality (formatting + analysis + tests)
composer code:check

# Fix code issues (format + refactor + test)
composer code:fix
```

## Recommended Workflow

### During Development
```bash
# Quick check while coding
composer pint:check
composer stan

# Or run the complete check
composer code:check
```

### Before Committing
```bash
# Fix issues and verify everything passes
composer code:fix
```

### CI/CD Integration
```bash
# In your CI pipeline
composer code:check
```

## Configuration Details

### Pint Configuration (`pint.json`)
```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": false,
        "modernize_strpos": true,
        "use_arrow_functions": false,
        "trailing_comma_in_multiline": {
            "elements": ["arrays", "arguments", "parameters"]
        }
    },
    "exclude": ["vendor", "node_modules", "coverage-html"]
}
```

### PHPStan Configuration (`phpstan.neon`)
- **Level 6**: Moderate strictness suitable for package development
- **Paths**: Only `src/` directory (excluding tests due to framework complexity)
- **Ignores**: Statamic/Laravel framework classes that may not be available during analysis
- **Memory**: 512M limit for analysis

### Rector Configuration (`rector.php`)
- **PHP Version**: Up to PHP 8.1
- **Rule Sets**: Code quality, coding style, dead code removal, early returns, type declarations
- **Parallel Processing**: Enabled for faster execution
- **Import Names**: Automatically imports short class names

## Integration with IDE

### PHPStorm
1. Install PHP CS Fixer plugin
2. Configure PHPStan plugin
3. Set Rector as external tool

### VS Code
1. Install "Laravel Pint" extension
2. Install "PHPStan" extension
3. Configure extensions to use project configurations

## Handling Issues

### PHPStan Issues
If PHPStan finds legitimate issues that can't be fixed immediately:
```bash
composer stan:baseline
```
This creates a baseline file to ignore current issues while preventing new ones.

### Rector Changes
Always review Rector changes before committing:
```bash
composer rector:dry  # Preview changes
composer rector      # Apply changes
git diff             # Review what changed
```

### Pint Formatting
Pint changes are generally safe to apply automatically:
```bash
composer pint        # Apply formatting
```

## Git Integration

The following files are ignored in `.gitignore`:
```
# Static analysis and code quality tools
phpstan-baseline.neon
.phpstan-cache/
.rector/
.pint.cache
```

## Pre-commit Hooks

Consider adding a pre-commit hook to run checks automatically:

```bash
#!/bin/sh
composer code:check
```

This ensures code quality checks are run before every commit.

## Troubleshooting

### Memory Issues
If PHPStan runs out of memory, increase the limit in `phpstan.neon`:
```neon
parameters:
    memoryLimitFile: 1G
```

### Performance Issues
- Rector: Uses parallel processing by default
- PHPStan: Consider using `--no-progress` flag in CI
- Pint: Use `pint:dirty` for faster formatting of changed files only

### Framework Dependencies
If you encounter issues with missing Laravel/Statamic classes:
1. Ensure `vendor/autoload.php` is available
2. Consider adding more ignores to `phpstan.neon`
3. For complex cases, use PHPStan baseline

## Benefits

1. **Consistency**: All code follows the same formatting standards
2. **Quality**: Catches potential bugs and code smells early
3. **Maintainability**: Keeps codebase modern and well-typed
4. **Developer Experience**: Automated fixes save time and mental overhead
5. **CI Integration**: Automated quality gates in deployment pipeline