<?php

namespace Photobooth\Utility;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class MarkdownUtility
{
    public static function render(string $path): string
    {
        $path = PathUtility::getAbsolutePath($path);
        if(!file_exists(PathUtility::getAbsolutePath($path))) {
            throw new \Exception('File cannot be found: ' . $path);
        }

        $content = file_get_contents($path);
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($content);
    }
}
