# MarkupJsonLDSchema Module v0.1.3

## General information

This module is all about arrays, either PHP or JSON-LD. JSON-LD schema arrays give your web pages a vastly improved chance of high visibility in SERPs and, at Google's whim, deliver more engaging results than simply a title, link and brief description.

While the inherent structure of a schema is predefined, there are thousands of combinations.

This module helps you dynamically create schemas from within your templates. Each schema can be configured to meet your requirements. You can even add your own ProcessWire schema classes to the module.

Some useful resources for learning more about JSON-LD schemas:

- https://developers.google.com/schemas/
- http://jsonld.com
- https://www.jamesdflynn.com/json-ld-schema-generator/

There are also several informative posts in the ProcessWire forums.

---

## Installation

1. Download or clone the module into:

```
/site/modules/MarkupJsonLDSchema/
```

2. Login to ProcessWire and go to:

```
Modules → Refresh
```

3. Install **MarkupJsonLDSchema**

Configuration is optional. The configuration fields simply provide convenient defaults for organization/business schema data.

---

## Usage

### Basic usage

In your template:

```php
<?php $jsonld = $modules->get('MarkupJsonLDSchema'); ?>

<script type="application/ld+json">
<?= $jsonld->render('LocalBusiness'); ?>
</script>
```

This will output a `LocalBusiness` schema based on the data saved in the module configuration.

---

### Overriding schema elements

A more complex example using the `$options` array:

```php
<?php
$jsonld = $modules->get('MarkupJsonLDSchema');

$options = [
    'logo' => $pages->get(1)->images->first->width(200)
];
?>

<script type="application/ld+json">
<?php
switch ($page->template) {
    case 'home':
        echo $jsonld->render('WebSite', $options);
        echo $jsonld->render('LocalBusiness', $options);
        break;

    case 'blog-post':
        echo $jsonld->render('Article');
        echo $jsonld->render('BreadcrumbList');
        break;

    default:
        echo $jsonld->render('WebPage', $options);
        echo $jsonld->render('LocalBusiness', $options);
        echo $jsonld->render('BreadcrumbList');
        break;
}
?>
</script>
```

Any value in the default schema may be overridden using `$options`, for example:

```php
<?php
$jsonld = $modules->get('MarkupJsonLDSchema');

$options["@type"] = "RealEstateAgent";
?>

<script type="application/ld+json">
<?= $jsonld->render('LocalBusiness', $options); ?>
</script>
```

This would render `"@type": "RealEstateAgent"` instead of `"LocalBusiness"`.

---

### Custom schema elements

You can extend the predefined schema arrays using the custom key in $options.
```php
<?php
$jsonld = $modules->get("MarkupJsonLDSchema");

$options["custom"] = [
    "foundingDate" => "2013",
    "areaServed" => "Worldwide",
    "taxID" => "ABN 99 999 999 123"
];
?>

<script type="application/ld+json">
<?= $jsonld->render('LocalBusiness', $options); ?>
</script>
```

The module will append these properties to the generated schema.

**Notes**

Custom fields must be simple text key/value pairs.

Keys and values are sanitized as text before being added to the schema.

Complex objects or nested structures (such as ImageObject, PostalAddress, or additional Person/Organization structures) are not supported through custom.

**If you need more complex schema structures, copy the closest schema class and adapt it for your use case.**

---

## Optional: caching schema output

Schema generation is lightweight and caching is not required. However, on high‑traffic sites you may wish to cache the rendered JSON‑LD.

Caching is intentionally left to the **template developer**, as caching strategy varies between projects.

Example using ProcessWire's `$cache` API:

```php
<?php
$jsonld = $modules->get("MarkupJsonLDSchema");

$options = [
    'logo' => $pages->get('template=bizinfo')->images('tags=logo')->last->width(200)
];

$cacheName = "jsonld-website-" . $page->id;
$schema = $cache->get($cacheName);

if(!$schema) {
    $schema = $jsonld->render('WebSite', $options);
    $cache->save($cacheName, $schema, 86400); // cache for 1 day
}
?>

<script type="application/ld+json">
<?= $schema ?>
</script>
```

Possible caching strategies include:

- clearing cache on page save
- caching per language
- caching different schema types separately

The module itself does not enforce caching so developers remain free to implement whatever strategy suits their project.

---

## Schemas included in the module

### Default config fields

```
address_locality
description
address_region
postcode
street_address
organization
logo
telephone
opening_hours
latitude
longitude
has_map
same_as
search_results_page
search_get_var
```

---

### Article

Outputs an `Article` schema for the current page. Defaults come from the page, including headline, publish and modified dates, description, body content, author, and URL. You can override the output with `$options` keys such as `@type`, `headline`, `description`, `articleBody`, and `image`.

---

### BreadcrumbList

Outputs a `BreadcrumbList` schema using the current page's parents. The module skips this schema on the home page. Useful for standard site navigation trails.

Based on the ProcessWire forum post by AndZyk:

https://processwire.com/talk/topic/13417-structured-data/

Thanks also to Macrura and others who contributed.

---

### Custom

A dummy schema with no defaults. Build the entire schema using `$options["custom"]`.

---

### Event

Outputs an `Event` schema for the current page. Defaults come from the page and module config where appropriate, with `organizer` pointing to the configured organization. Common overrides include `name`, `url`, `description`, `start_date`, `end_date`, `location`, and `offers`.

---

### LocalBusiness

Outputs a `LocalBusiness` schema using the module configuration as the primary data source. Useful for business name, address, telephone, opening hours, geo coordinates, map URL, and social profile links. Any of these values may be overridden in `$options`.

---

### NewsArticle

Outputs a `NewsArticle` schema for the current page. Defaults come from the page, including headline, dates, description, body, author, publisher reference, and URL. You can override the output with `$options` keys such as `@type`, `headline`, `description`, `articleBody`, and `image`.

---

### Organization

Outputs an `Organization` schema using the module configuration as the primary data source. Includes a canonical `@id` of `/#organization`, plus name, URL, description, logo, address, telephone, opening hours, social links, and optional geo coordinates.

---

### Person

`Person` is primarily driven by the `$options` array. The module config is still used where appropriate for shared values such as `organization`, `telephone`, `logo`, `same_as`, and address fields.

If you pass `worksFor` as an array, it should be an organization-like structure. The schema sanitizes common nested values such as `@id`, `url`, `sameAs`, and `address`.

```php
<?php
$jsonld = $modules->get('MarkupJsonLDSchema');

$options = [
    'name' => 'Jane Doe',
    'jobTitle' => 'Founder',
    'email' => 'jane@example.com',
    'worksFor' => [
        '@type' => 'Organization',
        '@id' => 'https://example.com/#organization',
        'name' => 'Acme Studio',
        'url' => 'https://example.com',
        'sameAs' => [
            'https://linkedin.com/company/acme',
            'https://github.com/acme',
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '123 Main St',
            'addressLocality' => 'Sydney',
            'addressRegion' => 'NSW',
            'postalCode' => '2000',
            'addressCountry' => 'AU',
        ],
    ],
];
?>

<script type="application/ld+json">
<?= $jsonld->render('Person', $options); ?>
</script>
```

---

### Product

Outputs a `Product` schema for the current page. Defaults come from page fields for brand, name, and description, while the publisher points to the configured organization. Optional overrides include `brand`, `name`, `description`, `image`, `rating_value`, and `review_count`.

---

### WebPage

Outputs a `WebPage` schema for the current page.

| Key | Default |
|----|----|
| @type | WebPage |
| url | `$page->httpUrl` or flat URL from `page_url` |
| name | `$page->get('seo_title|title|headline')` |
| description | `$page->get('seo_description|summary|blog-summary')` |
| image | supplied via `$options["image"]` |
| potentialAction | SearchAction if search page config is present |

---

### WebSite

Outputs a `WebSite` schema for the site home page. Defaults come from the home page and module config, including name, description, logo, publisher reference, and optional `SearchAction` using `search_results_page` and `search_get_var`.

---

## Creating your own schema classes

Developers can add new schema types.

Requirements:

**Location**

```
/site/modules/MarkupJsonLDSchema/schemas/
```

**File naming convention**

```
jsonld.SchemaName.php
```

Example:

```
jsonld.FAQPage.php
```

**Class naming convention**

```
ProcessWire\JsonLDSchemaName
```

Example:

```php
<?php namespace ProcessWire;

class JsonLDFAQPage {

    public static function getSchema(array $data = null, Page $page = null) {

        return [
            "@context" => "https://schema.org",
            "@type" => "FAQPage"
        ];

    }

}
```

Once created, it can be rendered normally:

```php
echo $jsonld->render('FAQPage');
```

---

## Change log

2026‑03‑09 – Refactor module structure, improved schema loader, configurable search settings added, new schemas added (v0.1.3)  
2025‑09‑29 – Added ProcessWire namespace & PHP 8.2 compatibility (v0.1.1)  
2016‑06‑19 – Initial release (v0.0.2)
