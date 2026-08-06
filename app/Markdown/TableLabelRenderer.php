<?php

namespace App\Markdown;

use DOMDocument;
use InvalidArgumentException;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class TableLabelRenderer implements NodeRendererInterface
{
    private TableRenderer $inner;

    public function __construct()
    {
        $this->inner = new TableRenderer;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof Table) {
            throw new InvalidArgumentException('Incompatible node type: '.get_class($node));
        }

        return $this->addHeaderLabels($this->inner->render($node, $childRenderer));
    }

    private function addHeaderLabels(string $tableHtml): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$tableHtml.'</div>');
        libxml_clear_errors();

        $table = $dom->getElementsByTagName('table')->item(0);

        if ($table === null) {
            return $tableHtml;
        }

        $headers = [];

        foreach ($table->getElementsByTagName('th') as $header) {
            $headers[] = trim($header->textContent);
        }

        foreach ($table->getElementsByTagName('tr') as $row) {
            foreach ($row->getElementsByTagName('td') as $index => $cell) {
                if (isset($headers[$index])) {
                    $cell->setAttribute('data-label', $headers[$index]);
                }
            }
        }

        $output = $dom->saveHTML($table);

        return $output === false ? $tableHtml : $output;
    }
}
