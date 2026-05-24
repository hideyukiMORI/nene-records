<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

final readonly class PublicMarkdownRenderer
{
    public static function isMarkdownFieldKey(string $fieldKey): bool
    {
        return $fieldKey === 'body';
    }

    public static function toSafeHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $converter = new MarkdownConverter($environment);

        return $converter->convert($markdown)->getContent();
    }
}
