<?php

namespace App\Services;

use App\Markdown\HeadingSlugExtension;
use App\Markdown\TableLabelRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;
use RuntimeException;

/**
 * Loads documentation content from Markdown files in resources/docs and
 * renders it to HTML so docs can be updated without a deploy.
 */
class DocsService
{
    /**
     * The markdown files that make up the reference sections, in nav order.
     */
    private const SECTION_FILES = [
        'quickstart',
        'introduction',
        'authentication',
        'age-query-endpoint',
        'hosted-flow',
        'consents',
        'webhooks',
        'error-codes',
        'rate-limits',
    ];

    /**
     * Render all reference sections.
     *
     * @return array<int, array{slug: string, title: string, html: string, headings: array<int, array{id: string, text: string, level: int}>}>
     */
    public function sections(): array
    {
        $sections = [];

        foreach (self::SECTION_FILES as $slug) {
            $sections[] = $this->load($slug);
        }

        return $sections;
    }

    /**
     * A lightweight search index of headings and body text per section.
     *
     * @return array<int, array{slug: string, title: string, text: string, headings: array<int, string>}>
     */
    public function searchIndex(): array
    {
        $index = [];

        foreach ($this->sections() as $section) {
            $text = trim(strip_tags(html_entity_decode($section['html'])));

            $index[] = [
                'slug' => $section['slug'],
                'title' => $section['title'],
                'text' => mb_substr($text, 0, 1200),
                'headings' => collect($section['headings'])->pluck('text')->values()->all(),
            ];
        }

        return $index;
    }

    /**
     * Parse a single markdown file with front matter.
     *
     * @return array{slug: string, title: string, html: string, headings: array<int, array{id: string, text: string, level: int}>}
     */
    private function load(string $slug): array
    {
        $path = resource_path('docs/'.$slug.'.md');

        if (! is_file($path)) {
            throw new RuntimeException("Documentation file [{$path}] not found.");
        }

        [$frontMatter, $markdown] = $this->splitFrontMatter((string) file_get_contents($path));

        $html = $this->converter()->convert($markdown);

        return [
            'slug' => $slug,
            'title' => $frontMatter['title'] ?? $slug,
            'html' => $this->rewriteLinks((string) $html),
            'headings' => $this->headings($html),
        ];
    }

    /**
     * Split YAML-ish front matter from the markdown body.
     *
     * @return array{0: array<string, string>, 1: string}
     */
    private function splitFrontMatter(string $contents): array
    {
        if (! str_starts_with($contents, '---')) {
            return [[], $contents];
        }

        $end = strpos($contents, "\n---", 3);

        if ($end === false) {
            return [[], $contents];
        }

        $block = substr($contents, 3, $end - 3);
        $body = substr($contents, $end + 4);

        $frontMatter = [];

        foreach (preg_split('/\r?\n/', $block) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $frontMatter[trim($key)] = trim($value);
            }
        }

        return [$frontMatter, $body];
    }

    /**
     * Prefix internal links with /docs/ so markdown links resolve within the docs.
     */
    private function rewriteLinks(string $html): string
    {
        return (string) preg_replace_callback(
            '/href="([^"]+)"/',
            function (array $matches): string {
                $href = $matches[1];

                if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '/') || str_starts_with($href, 'http')) {
                    return $matches[0];
                }

                return 'href="/docs/'.$href.'"';
            },
            $html,
        );
    }

    /**
     * Extract heading anchors for the in-page navigation.
     *
     * @return array<int, array{id: string, text: string, level: int}>
     */
    private function headings(RenderedContentInterface $html): array
    {
        preg_match_all('/<h([2-3]) id="([^"]+)">(.*?)<\/h\1>/', (string) $html, $matches, PREG_SET_ORDER);

        return array_map(
            fn (array $match) => [
                'id' => $match[2],
                'text' => trim(strip_tags($match[3])),
                'level' => (int) $match[1],
            ],
            $matches,
        );
    }

    private function converter(): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'max_nesting_level' => 20,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingSlugExtension);
        $environment->addRenderer(Table::class, new TableLabelRenderer, 10);

        return new MarkdownConverter($environment);
    }
}
