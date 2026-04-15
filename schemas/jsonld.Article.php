<?php namespace ProcessWire;

/**
 * JSON-LD Article schema (schema.org/Article).
 *
 * Outputs an Article type with headline, dates, author, publisher (@id), optional image, and citation.
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
     * @param array<string, mixed>|null $data Config/overrides: @type, headline, description, articleBody, image (Pageimage), citation.
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


        if (!empty($data['author'])) {
            $out['author'] = $data['author'];
        } else {
            $modules = wire('modules');
            if($modules->isInstalled('ProcessBlog')) {
                $blogConfigs = $modules->getConfig('ProcessBlog');
                $pages = wire('pages');

                $authorsPageID = $sanitizer->int($blogConfigs['blog-authors']);
                $authorsPage = $pages->get($authorsPageID);
                $authorPage = $pages->get($page->created_user_id);

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

        $citation = self::getCitation($data, $page);
        if (!empty($citation)) $out['citation'] = $citation;

        $textTools = new wireTextTools();
        $flds = 'blog_summary|seo_description|summary|blog_body|body|title';
        $description = !empty($data["description"]) ? $data["description"] : $page->get($flds);
        $description = $textTools->markupToText((string) $description);
        $description = preg_replace('/\h+/u', ' ', $description);      // collapse horizontal whitespace
        $description = preg_replace("/\n{3,}/", "\n\n", $description); // limit excessive blank lines
        $description = $textTools->truncate(
            $description,
            300,
            [
                'type' => 'sentence',
                'maxLength' => 300
            ]
        );
        $out['description'] = $description;

        $out = array_filter($out);
        return $out;
    }

    /**
     * Build a schema.org citation value from explicit overrides or blog source fields.
     *
     * @param array<string, mixed> $data
     * @param Page $page
     * @return array<string, mixed>|string|null
     */
    protected static function getCitation(array $data, Page $page) {
        if (!empty($data['citation'])) return $data['citation'];

        $sourceTitle = self::pageText($page, 'source_title');
        $sourceUrl = self::pageUrl($page, 'source_url');
        $sourceDoi = self::pageText($page, 'source_doi');
        $sourceJournal = self::pageText($page, 'source_journal');
        $sourceAuthors = self::pageText($page, 'source_authors');
        $sourcePublishDate = self::pageDate($page, 'source_publish_date');

        if ($sourceTitle || $sourceUrl || $sourceDoi || $sourceJournal || $sourceAuthors || $sourcePublishDate) {
            $citation = [
                '@type' => 'ScholarlyArticle',
            ];

            if ($sourceTitle) $citation['name'] = $sourceTitle;
            if ($sourceUrl) $citation['url'] = $sourceUrl;
            if ($sourcePublishDate) $citation['datePublished'] = $sourcePublishDate;
            if ($sourceJournal) {
                $citation['isPartOf'] = [
                    '@type' => 'Periodical',
                    'name' => $sourceJournal,
                ];
            }

            $doiUrl = self::doiUrl($sourceDoi);
            if ($sourceDoi) {
                $citation['identifier'] = [
                    '@type' => 'PropertyValue',
                    'propertyID' => 'DOI',
                    'value' => $sourceDoi,
                ];
            }
            if ($doiUrl) $citation['sameAs'] = $doiUrl;

            $authors = self::sourceAuthors($sourceAuthors);
            if ($authors) $citation['author'] = $authors;

            return $citation;
        }

        $blogCitations = self::pageTextFromMarkup($page, 'blog_citations');
        return $blogCitations ?: null;
    }

    protected static function pageText(Page $page, string $fieldName): string {
        if (!$page->hasField($fieldName)) return '';
        return trim(wire('sanitizer')->text((string) $page->get($fieldName)));
    }

    protected static function pageTextFromMarkup(Page $page, string $fieldName): string {
        if (!$page->hasField($fieldName)) return '';
        $textTools = new wireTextTools();
        $text = $textTools->markupToText((string) $page->get($fieldName));
        $text = preg_replace('/\h+/u', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim((string) $text);
    }

    protected static function pageUrl(Page $page, string $fieldName): string {
        if (!$page->hasField($fieldName)) return '';
        return trim(wire('sanitizer')->url((string) $page->get($fieldName)));
    }

    protected static function pageDate(Page $page, string $fieldName): string {
        if (!$page->hasField($fieldName)) return '';
        $value = $page->get($fieldName);
        if (empty($value)) return '';
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
        return $timestamp ? date('c', $timestamp) : '';
    }

    protected static function doiUrl(string $doi): string {
        $doi = trim($doi);
        if ($doi === '') return '';
        if (preg_match('#^https?://#i', $doi)) return wire('sanitizer')->url($doi);

        $doi = preg_replace('#^(doi:\s*)#i', '', $doi);
        return 'https://doi.org/' . ltrim((string) $doi, '/');
    }

    /**
     * Convert the free-text source authors field into schema.org Person values.
     *
     * @return array<int, array<string, string>>|null
     */
    protected static function sourceAuthors(string $authors): ?array {
        $authors = trim($authors);
        if ($authors === '' || preg_match('/^unknown authors?$/i', $authors)) return null;

        $parts = preg_split('/\s*(?:;|\band\b)\s*/i', $authors);
        $parts = array_values(array_filter(array_map('trim', $parts ?: [])));
        if (!$parts) $parts = [$authors];

        return array_map(
            fn(string $name): array => [
                '@type' => 'Person',
                'name' => wire('sanitizer')->text($name),
            ],
            $parts
        );
    }
}
