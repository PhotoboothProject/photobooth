<?php

namespace Photobooth\Controller\Api;

use Photobooth\Service\LanguageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'photobooth_api')]
class TranslationController extends AbstractController
{
    protected LanguageService $languageService;

    public function __construct(
        LanguageService $languageService,
    ) {
        $this->languageService = $languageService;
    }

    #[Route('/translations', name: '_translations')]
    public function index(): JsonResponse
    {
        return new JsonResponse($this->languageService->all());
    }
}
