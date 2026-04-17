<?php namespace ProcessWire;

/**
 * JSON-LD Product schema (schema.org/Product).
 *
 * Outputs a Product type with optional publisher, brand, image, aggregateRating, and offers.
 * Pass module config plus optional overrides/custom fields via $data.
 *
 * @see https://schema.org/Product
 */
class JsonLDProduct extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Build the Product schema array.
     *
     * @param array<string, mixed>|null $data Config/overrides: @type, name, description, brand, image (Pageimage), rating_value, review_count, offers, price, priceCurrency.
     * @param Page|null $page Page context (used for fallback name, description, brand from fields).
     * @return array<string, mixed> Schema array for json_encode.
     */
    public static function getSchema(?array $data = null, ?Page $page = null): array {
        $out = array();
        $data ??= [];
        $page ??= wire('page');
                 
        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');

        $out["@context"]    = "https://schema.org/";
        $out["@type"]       = !empty($data["@type"]) ? $sanitizer->text($data["@type"]) : "Product";
        $out["publisher"]   = ['@id' => rtrim($home->httpUrl, '/') . '/#organization'];
        $out["brand"]       = !empty($data['brand']) ? $sanitizer->text($data['brand']) : $page->get('brand|manufacturer|title');
        $out["name"]        = !empty($data['name']) ? $sanitizer->text($data['name']) : $page->get('seo_title|title|headline');
        $out["description"] = !empty($data['description']) ? $sanitizer->textarea($data['description']) : $page->get('seo_description|summary|blog-summary');
        if (!empty($data['image'])) {
            $out["image"]   = array(
                "@type"  => "ImageObject",
                "url"    => $sanitizer->url($data['image']->httpUrl),
                "height" => $sanitizer->text($data['image']->height),
                "width"  => $sanitizer->text($data['image']->width)
            );
         }
        
        if (!empty($data['rating_value']) || !empty($data['review_count'])) {
            $out['aggregateRating'] = array(
                "@type" => "AggregateRating",
                "ratingValue" => $sanitizer->text($data['rating_value']),
                "reviewCount" => $sanitizer->text($data['review_count'])
            
            );
         }

        $offers = self::getOffers($data, $page, $sanitizer);
        if (!empty($offers)) {
            $out['offers'] = $offers;
        }


        $out = array_filter($out);
        return $out;
    }

    protected static function getOffers(array $data, Page $page, Sanitizer $sanitizer): mixed
    {
        if (!empty($data['offers']) && is_array($data['offers'])) {
            if (self::isList($data['offers'])) {
                $offers = [];

                foreach ($data['offers'] as $offer) {
                    if (!is_array($offer)) continue;

                    $clean = self::sanitizeOffer($offer, $sanitizer);
                    if (!empty($clean)) {
                        $offers[] = $clean;
                    }
                }

                return $offers;
            }

            return self::sanitizeOffer($data['offers'], $sanitizer);
        }

        if (!self::hasValue($data['price'] ?? null)) {
            return null;
        }

        $offer = [
            '@type' => 'Offer',
            'url' => $page->httpUrl,
            'price' => $data['price'],
        ];

        if (self::hasValue($data['priceCurrency'] ?? null)) {
            $offer['priceCurrency'] = $data['priceCurrency'];
        } elseif (self::hasValue($data['price_currency'] ?? null)) {
            $offer['priceCurrency'] = $data['price_currency'];
        }

        return self::sanitizeOffer($offer, $sanitizer);
    }

    protected static function sanitizeOffer(array $offer, Sanitizer $sanitizer): array
    {
        $out = [
            '@type' => self::hasValue($offer['@type'] ?? null)
                ? $sanitizer->text((string) $offer['@type'])
                : 'Offer',
        ];

        $textFields = [
            'price',
            'priceCurrency',
            'priceValidUntil',
        ];

        $urlFields = [
            'url',
            'availability',
            'itemCondition',
        ];

        foreach ($textFields as $field) {
            if (self::hasValue($offer[$field] ?? null)) {
                $out[$field] = $sanitizer->text((string) $offer[$field]);
            }
        }

        foreach ($urlFields as $field) {
            if (self::hasValue($offer[$field] ?? null)) {
                $out[$field] = $sanitizer->url((string) $offer[$field]);
            }
        }

        if (self::hasValue($offer['seller'] ?? null)) {
            $seller = self::sanitizeSeller($offer['seller'], $sanitizer);
            if (!empty($seller)) {
                $out['seller'] = $seller;
            }
        }

        return array_filter($out, fn($value): bool => self::hasValue($value));
    }

    protected static function sanitizeSeller(mixed $seller, Sanitizer $sanitizer): mixed
    {
        if (is_array($seller)) {
            $out = [
                '@type' => self::hasValue($seller['@type'] ?? null)
                    ? $sanitizer->text((string) $seller['@type'])
                    : 'Organization',
            ];

            if (self::hasValue($seller['name'] ?? null)) {
                $out['name'] = $sanitizer->text((string) $seller['name']);
            }

            if (self::hasValue($seller['url'] ?? null)) {
                $out['url'] = $sanitizer->url((string) $seller['url']);
            }

            if (self::hasValue($seller['@id'] ?? null)) {
                $out['@id'] = $sanitizer->url((string) $seller['@id']);
            }

            return array_filter($out, fn($value): bool => self::hasValue($value));
        }

        if (is_scalar($seller)) {
            return [
                '@type' => 'Organization',
                'name' => $sanitizer->text((string) $seller),
            ];
        }

        return null;
    }

    protected static function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    protected static function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }
}?>
