# MarkupJsonLDSchema Module

## General information

This module automatically generates JSON-LD structured data for your ProcessWire pages. It supports both automatic generation via template mappings (recommended) and manual rendering from templates.

JSON-LD schemas improve your visibility in search results and enable rich snippets in Google.

Resources:
- https://developers.google.com/search/docs/appearance/structured-data
- https://schema.org
- https://search.google.com/test/rich-results

---

## Installation

1. Download or clone into `/site/modules/MarkupJsonLDSchema/`
2. Go to Modules → Refresh → Install **MarkupJsonLDSchema**
3. Optionally install **ProcessJsonLDSchemaConfig** for admin UI at Setup > Json-LD Schema

---

## Template Mappings (v0.3.0+)

The recommended way to use this module. No code required in templates.

### How it works

1. Go to **Setup > Json-LD Schema**
2. Open the **Template Mappings** section
3. Expand a template (e.g. `blog-post`)
4. Select schemas to generate (e.g. `BlogPosting`, `WebPage`)
5. Save — mapping fields will appear for each schema
6. Map schema keys to template fields (or leave "Auto" for defaults)
7. Save again

The module automatically injects `<script type="application/ld+json">` before `</head>` on every page that has mappings configured.

### Field mapping

Each schema has keys that can be mapped to any field in the template:

| Schema Key | Maps to | Example |
|-----------|---------|---------|
| `headline` | Text field | `title` |
| `description` | Text/Textarea | `blog_summary` |
| `image` | Image field | `image` |
| `datePublished` | Date field | `blog_date` |
| `articleBody` | Textarea | `body` |

If a key is left as "Auto", the schema uses its built-in fallback (usually `$page->get('field1|field2|field3')`).

### Backward compatibility

Templates without mappings continue using the legacy system (`schemas_list` field + `_markupschema.php`). You can migrate gradually.

---

## Manual Usage (Legacy)

You can still render schemas manually in templates:

```php
<?php $jsonld = $modules->get('MarkupJsonLDSchema'); ?>

<script type="application/ld+json">
<?= $jsonld->render('BlogPosting'); ?>
</script>
```

With options:

```php
$options = [
    'headline' => 'Custom Title',
    'image' => $page->image,
    'datePublished' => $page->blog_date,
];

echo $jsonld->render('Article', $options);
```

---

## Available Schemas

### Article

`schema.org/Article` — For general articles.

Keys: `headline`, `description`, `image`, `articleBody`, `datePublished`, `@type`

### BlogPosting *(new in v0.3.0)*

`schema.org/BlogPosting` — For blog posts. Includes `isPartOf` (parent blog), keywords, word count.

Keys: `headline`, `description`, `image`, `articleBody`, `blog_date`, `author_name`, `author_image`, `keywords`, `wordCount`

### BreadcrumbList

`schema.org/BreadcrumbList` — Auto-generated from page parents. Skipped on home page. No mapping needed.

### Custom

Empty schema. Build entirely via `$options['custom']` array.

### Event

`schema.org/Event` — For events with dates and location.

Keys: `name`, `description`, `start_date`, `end_date`, `location`, `offers`, `url`

### LocalBusiness

`schema.org/LocalBusiness` — Uses module config (organization, address, phone, hours, geo).

### NewsArticle

`schema.org/NewsArticle` — For news content.

Keys: `headline`, `description`, `image`, `articleBody`, `datePublished`, `@type`

### Organization

`schema.org/Organization` — Uses module config. Canonical `@id`: `/#organization`.

### Person

`schema.org/Person` — Driven by options. Personal fields not inherited from config.

Keys: `name`, `givenName`, `familyName`, `alternateName`, `description`, `url`, `email`, `telephone`, `jobTitle`, `image`, `worksFor`, `same_as`

### Product *(new in v0.3.0)*

`schema.org/Product` — Enhanced with offers, pricing, SKU, availability.

Keys: `name`, `description`, `image`, `brand`, `sku`, `price`, `currency`, `availability`, `condition`, `rating_value`, `review_count`, `url`

### WebPage

`schema.org/WebPage` — Generic page schema with optional SearchAction.

Keys: `name`, `description`, `image`, `@type`

### WebSite

`schema.org/WebSite` — Site-level schema with SearchAction.

Keys: `name`, `description`, `@type`

---

## Creating Custom Schemas

Add a file in `schemas/` following this convention:

**File:** `schemas/jsonld.FAQPage.php`

```php
<?php namespace ProcessWire;

class JsonLDFAQPage extends WireData {
    public static function getSchema(?array $data = null, ?Page $page = null): array {
        $page ??= wire('page');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'name' => $page->title,
        ];
    }
}
```

Then render with `$jsonld->render('FAQPage')` or select it in Template Mappings.

The module auto-detects keys by scanning `$data['key']` references in your schema file.

---

## Module Configuration

Global settings at Setup > Json-LD Schema:

| Field | Description |
|-------|-------------|
| Organization | Business name |
| Logo | Logo URL |
| Street / City / State / Postcode / Country | Address |
| Description | Business description |
| Contact Info | ContactPoint JSON |
| Phone | Telephone |
| Opening Hours | Business hours |
| Latitude / Longitude | Geo coordinates |
| Google Map URL | hasMap |
| Social Media URLs | sameAs (one per line) |
| Search Results Page | For SearchAction |
| Search GET Variable | For SearchAction |

---

## API

### `$jsonld->render($schemaName, $options, $page)`

Renders a schema as JSON string.

- `$schemaName` — Schema class name (e.g. `'BlogPosting'`)
- `$options` — Array of overrides (optional)
- `$page` — Page context (optional, defaults to current page)

### `$jsonld->getMappableKeys($schemaName = null)`

Returns mappable keys for all schemas (or a specific one).

```php
$keys = $jsonld->getMappableKeys();
// ['Article' => ['headline', 'description', 'image', ...], ...]

$keys = $jsonld->getMappableKeys('BlogPosting');
// ['BlogPosting' => ['headline', 'description', 'image', 'blog_date', ...]]
```

---

## Requirements

- ProcessWire 3.0.110+
- PHP 7.2+
