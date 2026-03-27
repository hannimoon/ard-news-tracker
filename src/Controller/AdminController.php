<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Service\NewsImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): Response
    {
        // Holt die Anzahl der News für das Dashboard
        $totalNews = $newsRepository->count([]);

        return $this->render('admin/index.html.twig', [
            'total_news' => $totalNews,
            'cron_active' => true, // Hier später Logik für DB-Settings einfügen
        ]);
    }

    #[Route('/admin/run-import', name: 'admin_run_import', methods: ['POST'])]
    public function runImport(NewsImporter $newsImporter): Response
    {
        try {
            $count = $newsImporter->import();
            $this->addFlash('success', "Erfolgreich $count neue Nachrichten importiert!");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Import: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
