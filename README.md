# CMS Mate Extension (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-mate-extension?label=stable&style=flat-square)](https://github.com/softspring/cms-mate-extension/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-mate-extension?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-mate-extension/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-mate-extension?style=flat-square)](https://github.com/softspring/cms-mate-extension/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-mate-extension/php?style=flat-square)](https://github.com/softspring/cms-mate-extension/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-mate-extension?style=flat-square)](https://packagist.org/packages/softspring/cms-mate-extension)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-mate-extension/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-mate-extension/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-mate-extension?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-mate-extension/tree/6.0)

> **Experimental package:** this extension is in active development and its tool contracts, configuration, and extension points may change before a stable release.

`softspring/cms-mate-extension` adds read-only Armonic CMS content tools to AI Mate.

It lets local AI agents inspect configured CMS content types, search CMS content, and read a single CMS content item through the host Symfony application's kernel and Doctrine setup.

## Installation

```bash
composer require softspring/cms-mate-extension:^6.0@dev
```

The extension requires `softspring/cms-bundle`, Doctrine ORM, and Symfony AI Mate.

AI Mate reads the package metadata from `composer.json`:

- `scan-dirs`: `src/Capability`
- `includes`: `config/config.php`
- `instructions`: `INSTRUCTIONS.md`

## Usage

Use the CMS Mate tools when an agent needs CMS content context.

Available tools:

- `cms-content-types`: list configured CMS content types.
- `cms-content-search`: search CMS contents by type, text, site, locale, and publication state.
- `cms-content-get`: get one CMS content item by type and id.

The tools boot the local Symfony application kernel and read CMS data through Doctrine. They are read-only and must not be used for imports, exports, fixture loading, or write operations.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-mate-extension/issues) and [send Pull Requests](https://github.com/softspring/cms-mate-extension/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is proprietary software. See [LICENSE](LICENSE).
