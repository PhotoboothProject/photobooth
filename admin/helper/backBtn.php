<?php

use Photobooth\Service\LanguageService;

/* Renders a standardized "Back" button.
 *
 * @param bool $newTab True if the link should open in a new tab. Defaults to false.
 * @param bool $scalable If true, the button will take full width (w-full). If false, it will have a fixed width (w-32) and not grow (flex-none). Defaults to false.
 * @return string The HTML for the "Back" button.
 */
function getBackBtn(bool $newTab = false, bool $scalable = false): string
{
    $label = 'back';
    $icon = 'fa fa-arrow-left'; // Hardcoded icon as per discussion
    $languageService = LanguageService::getInstance();

    // Base classes for consistent styling
    // We start with common classes, and then add width/flex properties based on $scalable
    $baseClasses = 'h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4 cursor-pointer';

    // Add scaling/width classes based on the $scalable parameter
    if ($scalable) {
        $baseClasses .= ' w-full'; // Allow to scale to full width
    } else {
        $baseClasses .= ' w-32 flex-none'; // Fixed width and prevent growing
    }

    $iconElement = empty($icon) ? '' : '<i class="mr-3 ' . $icon . '"></i>';
    $targetAttribute = $newTab ? '_blank' : '_self';

    return '
        <a href="javascript:history.back()" onclick="event.preventDefault(); history.back();" class="' . $baseClasses . '" target="' . $targetAttribute . '">
            ' . $iconElement . '
            ' . $languageService->translate($label) . '
        </a>
    ';
}
