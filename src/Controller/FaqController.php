<?php

namespace Photobooth\Controller;

use Photobooth\Utility\MarkdownUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    #[Route('/faq/', name: 'photobooth_faq')]
    public function index(): Response
    {
        return $this->render(
            'controller/faq.html.twig',
            [
                'content' => MarkdownUtility::render('docs/faq/index.md')
            ]
        );
    }
}
