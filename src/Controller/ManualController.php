<?php

namespace Photobooth\Controller;

use Photobooth\Utility\PathUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ManualController extends AbstractController
{
    #[Route('/manual/', name: 'photobooth_manual')]
    public function index(): Response
    {
        return $this->render(
            'controller/manual.html.twig',
            [
                'setup' => require PathUtility::getAbsolutePath('lib/configsetup.inc.php')
            ]
        );
    }
}
