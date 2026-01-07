<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;
use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator;

class LanguageService
{
    private string $locale;
    private Translator $translator;
    private ?MessageCatalogueInterface $catalogue = null;
    private NamedLogger $logger;
    /** @var array<string,bool> */
    private array $missingLogged = [];

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
        $this->logger = LoggerService::getInstance()->getLogger('i18n');

        // Only keep the catalogue cached when debug logging is enabled to avoid overhead in production
        if ($this->logger->getLevel() >= 2) {
            $this->catalogue = $translator->getCatalogue($this->locale);
        }
    }

    public function translate(string $id): string
    {
        $translated = $this->translator->trans($id, [], 'photobooth');

        // Detect locale-specific misses even if a fallback string was returned
        $isDefinedInLocale = $this->catalogue?->defines($id, 'photobooth') ?? true;

        if (!$isDefinedInLocale && !isset($this->missingLogged[$id])) {
            $this->missingLogged[$id] = true;
            $this->logger->debug('Missing translation', [
                'key'    => $id,
                'locale' => $this->locale,
            ]);
        }

        return $translated;
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
