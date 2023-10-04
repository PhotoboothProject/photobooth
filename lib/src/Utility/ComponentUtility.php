<?php

namespace Photobooth\Utility;

use Photobooth\Service\LanguageService;

class ComponentUtility
{
    public static function renderButtonLink(string $label, string $icon, string $href, bool $rotary = true, array $attributes = []): string
    {
        $languageService = LanguageService::getInstance();
        $attributes['class'] = 'button ' . ($attributes['class'] ?? '');
        if ($rotary) {
            $attributes['class'] .= ' rotaryfocus';
        }

        return '
            <a href="' . htmlspecialchars($href) . '" ' . self::renderAttributes($attributes) . '>
                <span ' . self::renderAttributes(['class' => 'button--icon']) . '>
                    <i ' . self::renderAttributes(['class' => $icon]) . '></i>
                </span>
                <span ' . self::renderAttributes(['class' => 'button--label']) . '>
                    ' . htmlspecialchars($languageService->translate($label)) . '
                </span>
            </a>
        ';
    }

    public static function renderButton(string $label, string $icon, string $command, bool $rotary = true, array $attributes = []): string
    {
        $languageService = LanguageService::getInstance();
        $attributes['class'] = 'button ' . $command . ' ' . ($attributes['class'] ?? '');
        if ($rotary) {
            $attributes['class'] .= ' rotaryfocus';
        }
        $attributes['type'] = 'button';
        $attributes['data-command'] = $command;

        return '
            <button ' . self::renderAttributes($attributes) . '>
                <span ' . self::renderAttributes(['class' => 'button--icon']) . '>
                    <i ' . self::renderAttributes(['class' => $icon]) . '></i>
                </span>
                <span ' . self::renderAttributes(['class' => 'button--label']) . '>
                    ' . htmlspecialchars($languageService->translate($label)) . '
                </span>
            </button>
        ';
    }

    protected static function renderAttributes(array $attributes): string
    {
        $newAttributes = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            $attributeName = strtolower($attributeName);
            if (!isset($newAttributes[$attributeName])) {
                $newAttributes[$attributeName] = htmlspecialchars((string)$attributeValue);
            }
        }
        $attributes = $newAttributes;

        $list = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            if ((string)$attributeValue !== '') {
                $list[] = $attributeName . '="' . trim((string)$attributeValue) . '"';
            }
        }

        return implode(' ', $list);
    }
}
