v0.3.0 - 2026-05-05
- NEW: Template Mappings — associate schemas to templates and map fields from the admin UI
- NEW: Module is now autoload; automatically injects JSON-LD based on template mappings
- NEW: getMappableKeys() method to auto-detect schema keys from source files
- NEW: BlogPosting schema (schema.org/BlogPosting) with isPartOf, keywords, wordCount
- NEW: Product (enhanced) with offers, brand, sku, price, currency, availability
- NEW: Article and NewsArticle now support `datePublished` key for custom date fields
- NEW: ProcessJsonLDSchemaConfig includes Template Mappings section with field validation
- NEW: Backward compatibility — templates without mappings still use legacy schemas_list
- CHANGED: _markupschema.php skips rendering when template has active mappings

v0.2.1 - 2026-04-16
- Improved Organization schema with contactPoint support
- Minor fixes

v0.1.5 - 2026-03-25
- Improved handling of nested arrays in $data['custom']
- Removed sanitizing $data['custom'] from schemas - all done in the module

v0.1.4 - 2026-03-24
- Improve schemas

v0.1.3 - 2026-03-09
- Added optional $data['custom'] key/value pairs for extending schema output
- Custom fields are sanitized as text and appended to schema output
- BreadcrumbList now includes the current page in the breadcrumb trail
- Standardised schema context to https://schema.org
- Minor internal improvements and cleanup

v0.1.1 - 2025-09-29
- Added ProcessWire namespace
- PHP 8.2 compatibility

v0.0.2 - 2016-06-19
- Initial release
