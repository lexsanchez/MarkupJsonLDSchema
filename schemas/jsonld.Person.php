<?php namespace ProcessWire;

/**
 * JSON-LD Person schema (schema.org/Person).
 *
 * Outputs a Person type using data passed via $data, with optional fallbacks from
 * the current page and module configuration for organization/contact details.
 *
 * @see https://schema.org/Person
 */
class JsonLDPerson extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Build the Person schema array.
     *
     * @param array<string, mixed>|null $data Overrides: @type, @id, name, givenName, familyName, alternateName, description, url, image, email, telephone, jobTitle, worksFor, same_as, street_address, address_locality, address_region, postcode, address_country.
     * @param Page|null $page Page context (used for fallback name, description, and url).
     * @return array<string, mixed> Schema array for json_encode.
     */
    public static function getSchema(?array $data = null, ?Page $page = null): array {
        $data ??= [];
        $page ??= wire('page');

        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');

        $out = [];
        $out['@context'] = 'https://schema.org';
        $out['@type'] = !empty($data['@type']) ? $sanitizer->text($data['@type']) : 'Person';

        if (!empty($data['@id'])) {
            $out['@id'] = $sanitizer->url($data['@id']);
        }

        $out['name'] = !empty($data['name'])
            ? $sanitizer->text($data['name'])
            : $sanitizer->text($page->get('seo_title|headline|title'));

        if (!empty($data['givenName'])) {
            $out['givenName'] = $sanitizer->text($data['givenName']);
        }

        if (!empty($data['familyName'])) {
            $out['familyName'] = $sanitizer->text($data['familyName']);
        }

        if (!empty($data['alternateName'])) {
            $out['alternateName'] = $sanitizer->text($data['alternateName']);
        }

        $out['description'] = !empty($data['description'])
            ? $sanitizer->textarea($data['description'])
            : $sanitizer->textarea($page->get('seo_description|summary|headline|title'));

        $out['url'] = !empty($data['url'])
            ? $sanitizer->url($data['url'])
            : $page->httpUrl;

        if (!empty($data['email'])) {
            $out['email'] = $sanitizer->email($data['email']);
        }

        if (!empty($data['telephone'])) {
            $out['telephone'] = $sanitizer->text($data['telephone']);
        }

        if (!empty($data['jobTitle'])) {
            $out['jobTitle'] = $sanitizer->text($data['jobTitle']);
        }

        if (!empty($data['image'])) {
            if (is_object($data['image']) && !empty($data['image']->httpUrl)) {
                $out['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $sanitizer->url($data['image']->httpUrl),
                ];
                if (!empty($data['image']->width)) {
                    $out['image']['width'] = $sanitizer->text($data['image']->width);
                }
                if (!empty($data['image']->height)) {
                    $out['image']['height'] = $sanitizer->text($data['image']->height);
                }
            } else {
                $out['image'] = $sanitizer->url($data['image']);
            }
        } elseif (!empty($data['logo'])) {
            if (is_object($data['logo']) && !empty($data['logo']->httpUrl)) {
                $out['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $sanitizer->url($data['logo']->httpUrl),
                ];
                if (!empty($data['logo']->width)) {
                    $out['image']['width'] = $sanitizer->text($data['logo']->width);
                }
                if (!empty($data['logo']->height)) {
                    $out['image']['height'] = $sanitizer->text($data['logo']->height);
                }
            } else {
                $out['image'] = $sanitizer->url($data['logo']);
            }
        }

        $worksFor = [];
        if (!empty($data['worksFor']) && is_array($data['worksFor'])) {
            $worksFor = self::sanitizeWorksFor($data['worksFor'], $sanitizer);
        } elseif (!empty($data['worksFor'])) {
            $worksFor = [
                '@type' => 'Organization',
                'name' => $sanitizer->text($data['worksFor']),
            ];
        } elseif (!empty($data['organization'])) {
            $worksFor = [
                '@type' => 'Organization',
                '@id' => rtrim($home->httpUrl, '/') . '/#organization',
                'name' => $sanitizer->text($data['organization']),
            ];
        }
        if (!empty($worksFor)) {
            $out['worksFor'] = $worksFor;
        }

        $addressParts = array_filter([
            'streetAddress' => $sanitizer->text($data['street_address'] ?? ''),
            'addressLocality' => $sanitizer->text($data['address_locality'] ?? ''),
            'addressRegion' => $sanitizer->text($data['address_region'] ?? ''),
            'postalCode' => $sanitizer->text($data['postcode'] ?? ''),
            'addressCountry' => $sanitizer->text($data['address_country'] ?? ''),
        ]);
        if (!empty($addressParts)) {
            $out['address'] = array_merge(['@type' => 'PostalAddress'], $addressParts);
        }

        $sameAs = [];
        if (!empty($data['same_as'])) {
            if (is_array($data['same_as'])) {
                $sameAs = array_values(array_filter(array_map('trim', $data['same_as'])));
            } else {
                $sameAs = array_values(array_filter(
                    array_map('trim', explode("\n", (string) $data['same_as']))
                ));
            }
        }
        if (!empty($sameAs)) {
            $out['sameAs'] = $sameAs;
        }

        // Add custom properties
        if (!empty($data['custom']) && is_array($data['custom'])) {
            foreach ($data['custom'] as $key => $value) {
                $cleanKey = $sanitizer->text((string) $key);
                $cleanVal = $sanitizer->text((string) $value);

                if ($cleanKey !== '' && $cleanVal !== '' && !isset($out[$cleanKey])) {
                    $out[$cleanKey] = $cleanVal;
                }
            }
        }

        return array_filter($out);
    }

    /**
     * Sanitize a worksFor organization-like payload.
     *
     * @param array<string, mixed> $worksFor
     * @param Sanitizer $sanitizer
     * @return array<string, mixed>
     */
    private static function sanitizeWorksFor(array $worksFor, Sanitizer $sanitizer): array {
        $out = [];

        foreach ($worksFor as $key => $value) {
            $cleanKey = (string) $key;

            if (in_array($cleanKey, ['@id', 'url'], true)) {
                $out[$cleanKey] = $sanitizer->url((string) $value);
                continue;
            }

            if ($cleanKey === 'sameAs') {
                if (is_array($value)) {
                    $out[$cleanKey] = array_values(array_filter(array_map(
                        static fn($item) => wire('sanitizer')->url((string) $item),
                        $value
                    )));
                } else {
                    $out[$cleanKey] = $sanitizer->url((string) $value);
                }
                continue;
            }

            if ($cleanKey === 'address' && is_array($value)) {
                $out[$cleanKey] = self::sanitizePostalAddress($value, $sanitizer);
                continue;
            }

            $out[$cleanKey] = $sanitizer->text((string) $value);
        }

        return array_filter($out);
    }

    /**
     * Sanitize a PostalAddress payload.
     *
     * @param array<string, mixed> $address
     * @param Sanitizer $sanitizer
     * @return array<string, mixed>
     */
    private static function sanitizePostalAddress(array $address, Sanitizer $sanitizer): array {
        $out = [];

        foreach ($address as $key => $value) {
            $out[(string) $key] = $sanitizer->text((string) $value);
        }

        return array_filter($out);
    }
}
