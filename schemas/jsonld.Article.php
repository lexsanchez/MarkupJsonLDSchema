<?php namespace ProcessWire;

/**
 * JSON-LD Article schema (schema.org/Article).
 *
 * Outputs an Article type with headline, dates, author, publisher (@id), and optional image.
 * Pass module config plus optional overrides via $data.
 *
 * @see https://schema.org/Article
 */
class JsonLDArticle extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Build the Article schema array.
     *
     * @param array<string, mixed>|null $data Config/overrides: @type, headline, description, articleBody, image (Pageimage).
     * @param Page|null $page Page context (used for mainEntityOfPage, headline, dates, author, body).
     * @return array<string, mixed> Schema array for json_encode.
     */
    public static function getSchema(?array $data = null, ?Page $page = null): array {
        $out = array();
        $data ??= [];
        $page ??= wire('page');
         
        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');
            
        $pageURL = !empty($data['page_url']) ? $home->httpUrl . $data['page_url'] : $page->httpUrl;
        
                            
            $pageHeadline = !empty($data['headline']) ? $sanitizer->text($data['headline']) : $page->get('seo_title|headline|title');
            
            $out["@context"]         = "https://schema.org/";
            $out["@type"]            = !empty($data["@type"]) ? $sanitizer->text($data["@type"]) : "Article";
            $out["mainEntityOfPage"] = [
                "@type" => 'WebPage',
                "@id"   => rtrim($page->httpUrl, '/') . '/#webpage',
            ];
            $out["headline"]         = !empty($data["headline"]) ? $sanitizer->text($data["headline"]) : $pageHeadline;
            $out["url"]              = $pageURL;

            $out["datePublished"]   = date('c', $page->created);
            $out["dateModified"]    = date('c', $page->modified);


        if (!empty($data['author']) && is_array($data['author'])) {
            $out['author'] = $data['author'];
        } else {
            $modules = wire('modules');
            if($modules->isInstalled('ProcessBlog')) {
                $blogConfigs = $modules->getConfig('ProcessBlog');
                $pages = wire('pages');

                $authorsPageID = $sanitizer->int($blogConfigs['blog-authors']);
                $authorsPage = $pages->get($authorsPageID);
                $authorPage = $pages->get($page->created_user_id);
                bd($authorsPage);

                if (!$authorsPage instanceof NullPage && !$authorPage instanceof NullPage) {
                    $authorSlug = $sanitizer->pageName($authorPage->title);
                    $out['author'] = [
                        '@type' => 'Person',
                        '@id' => rtrim($authorsPage->httpUrl, '/') . "/$authorSlug" .  '/#person',
                        'name' => $authorPage->title,
                    ];
                }
            }
        }


            $out["publisher"] = ['@id' => rtrim($home->httpUrl, '/') . '/#organization'];        
            if (!empty($data['image'])) {
                $out["image"]    = array(
                    "@type"  => "ImageObject",
                    "url"    => $sanitizer->url($data['image']->httpUrl),
                    "height" => $sanitizer->int($data['image']->height),
                    "width"  => $sanitizer->int($data['image']->width)
                );
             }

            $textTools = new wireTextTools();
            $body = !empty($data["articleBody"]) ? $data["articleBody"] : $page->get('blog_body|body');
            $body = $textTools->markupToText((string) $body);
            $body = preg_replace('/\h+/u', ' ', $body);      // collapse horizontal whitespace
            $body = preg_replace("/\n{3,}/", "\n\n", $body); // limit excessive blank lines
            $body = trim($body);

            $description = !empty($data["articleBody"]) ? $data["description"] : $page->get('blog_summary|seo_description|summary|title');
            $description = $textTools->markupToText((string) $body);
            $description = preg_replace('/\h+/u', ' ', $body);      // collapse horizontal whitespace
            $description = preg_replace("/\n{3,}/", "\n\n", $body); // limit excessive blank lines
            $description = trim($description);
            
            $out['description'] = $description;
            $out["articleBody"] = $body;

        $out = array_filter($out);
        return $out;
    }
}
