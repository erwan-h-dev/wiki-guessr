<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\GameSession;
use App\Service\WikipediaService;
use App\Service\HtmlCleaner;
use App\Service\GameNavigationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly WikipediaService $wikipediaService,
        private readonly HtmlCleaner $htmlCleaner,
        private readonly GameNavigationService $navigationService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{id}', name: 'game_start')]
    public function start(Challenge $challenge): Response
    {
        // Create a new game session
        $session = new GameSession();
        $session->setChallenge($challenge);
        $session->setStartTime(new DateTime());
        $session->setCompleted(false);

        // Initialize path with start page
        $session->setPath([$challenge->getStartPage()]);
        $session->addPageVisit($challenge->getStartPage());

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $this->render('game/play.html.twig', [
            'session' => $session,
            'challenge' => $session->getChallenge(),
        ]);
    }

    #[Route('/{id}/page/{title}', name: 'game_load_page', requirements: ['title' => '.+'])]
    public function page(
        GameSession $session,
        string $title,
        Request $request
    ): Response {
        if ($session->isCompleted()) {
            return $this->render('game/completed.html.twig', [
                'session' => $session,
            ]);
        }

        // Handle navigation
        $isBackNavigation = $this->navigationService->navigateToPage($session, $title);

        // Check for victory
        if ($this->navigationService->isTargetReached($session, $title)) {
            $session->complete();
            $this->entityManager->flush();

            $statistics = $this->navigationService->calculateStatistics($session);

            return $this->render('game/victory.html.twig', [
                'session' => $session,
                'statistics' => $statistics,
                'challenge' => $session->getChallenge(),
            ]);
        }

        // Fetch Wikipedia content
        $pageData = $this->wikipediaService->getPage($title);
        $cleanedContent = $this->htmlCleaner->clean($pageData['content']['*'], $session->getId());
        return $this->render('game/_wiki_content.html.twig', [
            'content' => $cleanedContent,
            'title' => $pageData['displaytitle'],
            'session' => $session,
            'challenge' => $session->getChallenge(),
        ]);

    }
}
