<?php

namespace App\Controller;

use App\Entity\CronConfig;
use App\Repository\NewsRepository;
use App\Repository\CronConfigRepository;
use App\Service\NewsImporter;
use App\Service\TimezoneManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

/**
 * Admin controller for managing news import and cron configuration.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class AdminController extends AbstractController
{
    /**
     * Displays the admin dashboard with news count and cron config.
     *
     * @param Request $request The HTTP request
     * @param NewsRepository $newsRepository Repository for news
     * @param CronConfigRepository $cronConfigRepository Repository for cron config
     * @param TimezoneManager $tzManager Timezone conversion service
     * @return Response The dashboard response
     */
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Request $request,
                          NewsRepository $newsRepository,
                          CronConfigRepository $cronConfigRepository,
                          TimezoneManager $tzManager,): Response
    {
        // Holt die Anzahl der News für das Dashboard
        $totalNews = $newsRepository->count([]);
        $config = $cronConfigRepository->findOneBy([]);
         $userTz = $request->getSession()->get('user_timezone', 'Europe/Berlin');

        $localizedTime = $config 
            ? $tzManager->utcToLocal($config->getScheduleTime(), $userTz) 
            : '08:00';

        return $this->render('admin/index.html.twig', [
            'total_news' => $totalNews,
            'cron_config' => $config,
            'localized_time' => $localizedTime
        ]);
    }

    /**
     * Runs the news import manually.
     *
     * @param NewsImporter $newsImporter The news importer service
     * @return Response Redirect response to dashboard
     */
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


    /**
     * Saves the cron configuration.
     *
     * @param Request $request The HTTP request
     * @param EntityManagerInterface $em The entity manager
     * @param TimezoneManager $tzManager The timezone manager
     * @return RedirectResponse Redirect to dashboard
     */
    #[Route('/admin/cron/save', name: 'admin_cron_save', methods: ['POST'])]
    public function save(
        Request $request, 
        EntityManagerInterface $em, 
        TimezoneManager $tzManager
        ): RedirectResponse
    {
        // 1. Die einzige Konfiguration laden
        $config = $em->getRepository(CronConfig::class)->findOneBy([]) ?? new CronConfig();

        // 2. Daten aus dem Request holen
        $localTime = $request->request->get('schedule_time', '08:00');
        $userTz = $request->request->get('user_timezone', 'UTC');

        // 3. In UTC umwandeln & speichern
        $utcTime = $tzManager->localToUtc($localTime, $userTz);
        $config->setScheduleTime($utcTime);
        $config->setIsActive($request->request->has('cron_active'));
        $em->persist($config);
        $em->flush();   

        $request->getSession()->set('user_timezone', $userTz);
        
        $projectDir = $this->getParameter('kernel.project_dir');
        $process = new Process(['php', 'bin/console', 'messenger:stop-workers'], $projectDir);
        $process->run();  

        $this->addFlash('success', 'Einstellungen wurden gespeichert.');

        return $this->redirectToRoute('admin_dashboard');
    }

}
