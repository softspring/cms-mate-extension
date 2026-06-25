# CMS Bridge

Use the CMS Mate tools when you need read-only CMS content context.

Available tools:

- `cms-content-types`: list configured CMS content types.
- `cms-content-search`: search CMS contents by type, text, site and locale.
- `cms-content-get`: get one CMS content item by type and id.

The tools boot the local Symfony application kernel and read CMS data through Doctrine. They are read-only and must not
be used for imports, exports, fixture loading or write operations.
