<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator;

class LanguageService
{
    private string $locale;
    private Translator $translator;

    public function __construct()
    {
        $this->locale = ConfigurationService::getInstance()->getConfiguration()['ui']['language'];

        $translator = new Translator($this->locale);
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('json', new JsonFileLoader());

        $path = PathUtility::getAbsolutePath(FolderEnum::LANG->value);
        if (PathUtility::isAbsolutePath($path)) {
            foreach (new \DirectoryIterator($path) as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'json') {
                    continue;
                }
                $translator->addResource('json', $path . '/' . $file->getFilename(), $file->getBasename('.' . $file->getExtension()), 'photobooth');
            }
        }

        $this->translator = $translator;
    }

    public function translate(string $id): string
    {
        return $this->translator->trans($id, [], 'photobooth');
    }

    public function all(): array
    {
        return $this->translator->getCatalogue($this->locale)->all('photobooth');
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
