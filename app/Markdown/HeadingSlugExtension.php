<?php

namespace App\Markdown;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Renderer\Block\HeadingRenderer;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Adds an id attribute to every heading so doc sections can be deep-linked.
 */
final class HeadingSlugExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Heading::class, new class(new HeadingRenderer) implements NodeRendererInterface
        {
            public function __construct(private readonly HeadingRenderer $inner) {}

            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                $rendered = $this->inner->render($node, $childRenderer);

                if (! $node instanceof Heading) {
                    return $rendered;
                }

                $text = $node->firstChild()?->getLiteral() ?? '';

                if ($text === '') {
                    return $rendered;
                }

                return preg_replace('/^<h(\d)/', '<h$1 id="'.Str::slug($text).'"', $rendered, 1) ?? $rendered;
            }
        }, 100);
    }
}
