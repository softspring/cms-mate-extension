# CMS Mate Extension Features

Functional definition for `softspring/cms-mate-extension`.

This package provides AI Mate tools that expose read-only Armonic CMS content context from a local Symfony application.

## Purpose

- Give AI Mate controlled access to CMS content structure.
- Let agents inspect content types and content records without writing CMS data.
- Reuse the host Symfony application's kernel, container, Doctrine entity manager, and CMS configuration.
- Keep AI Mate CMS access separate from HTTP API and MCP server plugins.

## Main Features

- AI Mate package metadata through the `extra.ai-mate` Composer section.
- AI Mate instructions in `INSTRUCTIONS.md`.
- Service configuration loaded from `config/config.php`.
- Read-only tool for listing configured CMS content types.
- Read-only tool for searching CMS content by type, text, site, locale, and publication state.
- Read-only tool for reading one CMS content item by content type and id.
- Symfony kernel bootstrap helper for local application access.
- CMS content reader service using Doctrine and CMS serializers.
- Standard QA scripts for code style, static analysis, unit tests, and dependency compatibility.
- CI workflow for regular and lowest supported dependency sets.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Let AI Mate discover the package metadata from Composer.
- Use the `cms-content-types`, `cms-content-search`, and `cms-content-get` tools when an agent needs CMS context.
- Keep the tools read-only. Do not use this package for imports, exports, fixture loading, or CMS mutations.

## Extension Points

- Add new AI Mate tools under `Capability`.
- Add reusable local application readers under `Service`.
- Register new services from `config/config.php`.
- Keep tools explicit, read-only, and scoped to the context agents really need.

## Current Limits

- The package only exposes read-only content tools.
- The package expects an `App\Kernel` class in the host Symfony project.
- The package is intended for local AI Mate usage, not as a public HTTP API.
- The package does not ship frontend assets.
