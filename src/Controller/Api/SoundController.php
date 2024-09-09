<?php

namespace Photobooth\Controller\Api;

use Photobooth\Service\SoundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'photobooth_api')]
class SoundController extends AbstractController
{
    protected SoundService $soundService;

    public function __construct(
        SoundService $soundService,
    ) {
        $this->soundService = $soundService;
    }

    #[Route('/sounds', name: '_sounds')]
    public function index(): JsonResponse
    {
        return new JsonResponse($this->soundService->all());
    }
}
