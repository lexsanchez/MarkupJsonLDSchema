<?php namespace ProcessWire;

/**
 * JSON-LD WebPage schema (schema.org/WebPage).
 *
 * Outputs a WebPage type with url, name, description, publisher (@id), optional image, and optional SearchAction from config (search_results_page, search_get_var).
 *
 * @see https://schema.org/WebPage
 */
class JsonLDWebPage extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Build the WebPage schema array.
     *
     * @param array<string, mixed> $data Config/overrides: @type, name, description, image (Pageimage), search_results_page, search_get_var.
     * @param Page $page Page context (used for url, name, description fallbacks).
     * @return array<string, mixed> Schema array for json_encode.
     */
    public static function getSchema(array $data, Page $page): array {
        $out = array();
                 
        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');
            
 //       $pageURL = !empty($data['page_url']) ? $home->httpUrl . $data['page_url'] : $page->httpUrl;

        $pageURL = $page->httpUrl;

        if (!empty($data['page_url'])) {
            $url = trim($data['page_url']);

            // Absolute URL? Use as-is
            if (preg_match('~^https?://~i', $url)) {
                $pageURL = $url;

            } else {
                // Relative path → safely join with homepage
                $pageURL = rtrim($home->httpUrl, '/') . '/' . ltrim($url, '/');
            }
        }


        $out["@context"]    = "https://schema.org/";
        $out["@type"]       = !empty($data["@type"]) ? $sanitizer->text($data["@type"]) : "WebPage";
        $out["url"]         = $pageURL;
        $out["name"]        = !empty($data['name']) ? $sanitizer->text($data['name']) : $page->get('seo_title|title|headline');
        $out["description"] = !empty($data['description']) ? $sanitizer->textarea($data['description']) : $page->get('seo_description|summary|blog-summary');
        $out["publisher"]   = ['@id' => rtrim($home->httpUrl, '/') . '/#organization'];
        if (!empty($data['image'])) {
            $out["image"]   = array(
                "@type"  => "ImageObject",
                "url"    => $sanitizer->url($data['image']->httpUrl),
                "height" => $sanitizer->text($data['image']->height),
                "width"  => $sanitizer->text($data['image']->width)
            );
         }

        $searchPage = trim($sanitizer->text($data['search_results_page'] ?? ''));
        $searchVar = trim($sanitizer->text($data['search_get_var'] ?? ''));
        if ($searchPage !== '' && $searchVar !== '') {
            $searchPage = '/' . trim($searchPage, '/') . '/';
            $out['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => rtrim($home->httpUrl, '/') . $searchPage . '?' . $searchVar . '={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ];
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

        $out = array_filter($out);
        return $out;
    }
}?>
