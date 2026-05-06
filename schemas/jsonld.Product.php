<?php namespace ProcessWire;

/**
 * JSON-LD Product schema (schema.org/Product) — Enhanced version.
 *
 * @see https://schema.org/Product
 */
class JsonLDProductSchema extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * @param array<string, mixed>|null $data Keys: name, description, image, brand, sku, price, currency, availability, condition, rating_value, review_count, url
     * @param Page|null $page
     * @return array<string, mixed>
     */
    public static function getSchema(?array $data = null, ?Page $page = null): array
    {
        $data ??= [];
        $page ??= wire('page');
        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');

        $out = [];
        $out['@context'] = 'https://schema.org/';
        $out['@type'] = 'Product';
        $out['name'] = !empty($data['name'])
            ? $sanitizer->text($data['name'])
            : $page->get('seo_title|title|headline');
        $out['description'] = !empty($data['description'])
            ? $sanitizer->textarea($data['description'])
            : $page->get('seo_description|summary|body');
        $out['url'] = !empty($data['url']) ? $sanitizer->url($data['url']) : $page->httpUrl;

        // Image
        if (!empty($data['image'])) {
            $img = $data['image'];
            if (is_object($img) && !empty($img->httpUrl)) {
                $out['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $sanitizer->url($img->httpUrl),
                    'height' => (string)$img->height,
                    'width' => (string)$img->width,
                ];
            } elseif (is_string($img)) {
                $out['image'] = $sanitizer->url($img);
            }
        }

        // Brand
        if (!empty($data['brand'])) {
            $out['brand'] = [
                '@type' => 'Brand',
                'name' => $sanitizer->text($data['brand']),
            ];
        }

        // SKU
        if (!empty($data['sku'])) {
            $out['sku'] = $sanitizer->text($data['sku']);
        }

        // Offers (price)
        if (!empty($data['price'])) {
            $offer = [
                '@type' => 'Offer',
                'price' => $sanitizer->text($data['price']),
                'priceCurrency' => !empty($data['currency']) ? $sanitizer->text($data['currency']) : 'USD',
                'url' => $out['url'],
            ];
            if (!empty($data['availability'])) {
                $offer['availability'] = 'https://schema.org/' . $sanitizer->text($data['availability']);
            } else {
                $offer['availability'] = 'https://schema.org/InStock';
            }
            if (!empty($data['condition'])) {
                $offer['itemCondition'] = 'https://schema.org/' . $sanitizer->text($data['condition']);
            }
            $offer['seller'] = ['@id' => rtrim($home->httpUrl, '/') . '/#organization'];
            $out['offers'] = $offer;
        }

        // Aggregate Rating
        if (!empty($data['rating_value']) && !empty($data['review_count'])) {
            $out['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $sanitizer->text($data['rating_value']),
                'reviewCount' => $sanitizer->text($data['review_count']),
            ];
        }

        return array_filter($out);
    }
}
