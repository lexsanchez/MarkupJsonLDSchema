<?php namespace ProcessWire;

/**
 * JSON-LD BlogPosting schema (schema.org/BlogPosting).
 *
 * @see https://schema.org/BlogPosting
 */
class JsonLDBlogPosting extends WireData {

    public function __construct() {
        parent::__construct();
    }

    /**
     * @param array<string, mixed>|null $data Keys: headline, description, image, articleBody, author_name, author_image, blog_date, keywords, wordCount
     * @param Page|null $page
     * @return array<string, mixed>
     */
    public static function getSchema(?array $data = null, ?Page $page = null): array
    {
        $data ??= [];
        $page ??= wire('page');
        $home = wire('pages')->get('/');
        $sanitizer = wire('sanitizer');

        $pageURL = !empty($data['page_url']) ? $home->httpUrl . $data['page_url'] : $page->httpUrl;

        $out = [];
        $out['@context'] = 'https://schema.org/';
        $out['@type'] = 'BlogPosting';
        $out['@id'] = rtrim($pageURL, '/') . '/#BlogPosting';
        $out['mainEntityOfPage'] = $pageURL;
        $out['headline'] = !empty($data['headline'])
            ? $sanitizer->text($data['headline'])
            : $page->get('seo_title|headline|title');
        $out['name'] = $out['headline'];
        $out['description'] = !empty($data['description'])
            ? $sanitizer->text($data['description'])
            : $page->get('seo_description|blog_summary|summary');
        $out['url'] = $pageURL;

        // Dates
        $blogDate = !empty($data['blog_date']) ? $data['blog_date'] : $page->get('blog_date|created');
        if (is_numeric($blogDate)) {
            $out['datePublished'] = date('Y-m-d', (int)$blogDate);
        } elseif ($blogDate) {
            $out['datePublished'] = date('Y-m-d', strtotime($sanitizer->text($blogDate)));
        }
        $out['dateModified'] = date('Y-m-d', $page->modified);

        // Author
        $authorName = !empty($data['author_name'])
            ? $sanitizer->text($data['author_name'])
            : wire('users')->get($page->created_users_id ?: $page->created_user_id)->get('title|name');
        $author = [
            '@type' => 'Person',
            'name' => $authorName,
            'url' => $pageURL,
        ];
        if (!empty($data['author_image'])) {
            $img = $data['author_image'];
            if (is_object($img) && !empty($img->httpUrl)) {
                $author['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $img->httpUrl,
                    'height' => (string)$img->height,
                    'width' => (string)$img->width,
                ];
            }
        }
        $out['author'] = $author;

        // Publisher
        $out['publisher'] = ['@id' => rtrim($home->httpUrl, '/') . '/#organization'];

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
            }
        }

        // isPartOf (Blog)
        $blogParent = $page->parent;
        if ($blogParent && $blogParent->id) {
            $out['isPartOf'] = [
                '@type' => 'Blog',
                '@id' => $blogParent->httpUrl,
                'name' => $blogParent->get('headline|title'),
                'publisher' => ['@id' => rtrim($home->httpUrl, '/') . '/#organization'],
            ];
        }

        // Keywords
        if (!empty($data['keywords'])) {
            $kw = $data['keywords'];
            if (is_object($kw) && $kw instanceof WireArray) {
                $out['keywords'] = $kw->explode('title');
            } elseif (is_string($kw)) {
                $out['keywords'] = array_map('trim', explode(',', $kw));
            }
        }

        // Word count
        if (!empty($data['wordCount'])) {
            $out['wordCount'] = (string)$sanitizer->int($data['wordCount']);
        } elseif (!empty($data['articleBody'])) {
            $out['wordCount'] = (string)str_word_count(strip_tags($data['articleBody']));
        } else {
            $body = $page->get('body|blog_body');
            if ($body) $out['wordCount'] = (string)str_word_count(strip_tags($body));
        }

        // Article body
        if (!empty($data['articleBody'])) {
            $out['articleBody'] = $sanitizer->textarea($data['articleBody']);
        }

        return array_filter($out);
    }
}
