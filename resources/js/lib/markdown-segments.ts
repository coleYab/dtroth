interface CodeGroupItem {
    language: string;
    code: string;
}

interface HtmlSegment {
    type: 'html';
    html: string;
}

interface CodeGroupSegment {
    type: 'code-group';
    items: CodeGroupItem[];
}

export type DocSegment = HtmlSegment | CodeGroupSegment;

const CODE_BLOCK_RE = /<pre><code class="language-([\w+-]+)">([\s\S]*?)<\/code><\/pre>/g;

const decodeEntities = (html: string): string =>
    html.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&');

const parseCodeBlock = (match: RegExpExecArray): CodeGroupItem => ({
    language: match[1],
    code: decodeEntities(match[2]),
});

/**
 * Splits rendered doc HTML into segments, grouping consecutive code blocks
 * (same example across languages) into a single code-group segment so they
 * can be rendered as tabs.
 */
export function segmentDocHtml(html: string): DocSegment[] {
    const segments: DocSegment[] = [];
    let cursor = 0;
    let pending: CodeGroupItem[] = [];

    for (const match of html.matchAll(CODE_BLOCK_RE)) {
        const index = match.index ?? 0;

        if (index > cursor) {
            if (pending.length > 0) {
                segments.push({ type: 'code-group', items: pending });
                pending = [];
            }

            segments.push({ type: 'html', html: html.slice(cursor, index) });
        }

        pending.push(parseCodeBlock(match));
        cursor = index + match[0].length;
    }

    if (pending.length > 0) {
        segments.push({ type: 'code-group', items: pending });
    }

    if (cursor < html.length) {
        segments.push({ type: 'html', html: html.slice(cursor) });
    }

    if (segments.length === 0) {
        return [{ type: 'html', html }];
    }

    return segments;
}
