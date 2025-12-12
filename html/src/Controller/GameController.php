<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\GameSession;
use App\Entity\Player;
use App\Service\WikipediaService;
use App\Service\HtmlCleaner;
use App\Service\GameNavigationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function start(Request $request, Challenge $challenge): Response
    {
        // Get the current player from request attributes (set by PlayerSubscriber)
        $player = $request->attributes->get('_player');

        $session = $this->entityManager->getRepository(GameSession::class)
            ->findOneBy([
                'challenge' => $challenge,
                'player' => $player
            ]);
        if (!$session) {
            // Create a new game session
            $session = new GameSession();
            $session->setChallenge($challenge);
            $session->setPlayer($player);
            $session->setStartTime(new DateTime());
            $session->setCompleted(false);

            // Initialize path with start page
            $session->setPath([$challenge->getStartPage()]);
            $session->addPageVisit($challenge->getStartPage());

            $this->entityManager->persist($session);
            $this->entityManager->flush();
        }

        return $this->render('game/play.html.twig', [
            'session' => $session,
            'challenge' => $session->getChallenge(),
        ]);
    }

    #[Route('/{id}/page/{title}', name: 'game_load_page', requirements: ['title' => '.+'])]
    public function page(Request $request, GameSession $session, string $title): Response
    {
        if ($session->isCompleted()) {
            $statistics = $this->navigationService->calculateStatistics($session);
            // Pour les requêtes Turbo Frame, utiliser Turbo Stream pour afficher un message
            if ($request->headers->has('Turbo-Frame')) {
                $html = $this->renderView('game/_game_already_finished.html.twig', [
                    'session' => $session,
                    'statistics' => $statistics
                ]);
                return new Response($html, 200, ['Content-Type' => 'text/vnd.turbo-stream.html']);
            }
            return $this->redirectToRoute('game_finished', [
                'id' => $session->getId()
            ]);
        }

        $statistics = null;
        $this->navigationService->navigateToPage($session, $title);

        // Fetch Wikipedia content
        try {
            $pageData = $this->wikipediaService->getPage($title);
        } catch (Exception $e) {
            return new Response('Page not found', 404);
        }

        $cleanedContent = $this->htmlCleaner->clean($pageData['content']['*'], $session->getId());

        // Check for victory
        if ($this->navigationService->isTargetReached($session, $title)) {
            $session->complete();
            $this->entityManager->flush();

            $statistics = $this->navigationService->calculateStatistics($session);
        }

        $params = [
            'content' => $cleanedContent,
            'title' => $title,
            'session' => $session,
            'challenge' => $session->getChallenge(),
            'statistics' => $statistics,
        ];

        // Requête depuis un Turbo Frame : renvoyer turbo-streams
        if ($request->headers->has('Turbo-Frame')) {
            $html = $this->renderView('game/_wiki_content.html.twig', $params);
            return new Response($html, 200, ['Content-Type' => 'text/vnd.turbo-stream.html']);
        }

        // Chargement direct (non-frame)
        return $this->render('game/_wiki_content.html.twig', $params);
    }

    #[Route('/{id}/extract/{title}', name: 'game_page_extract', requirements: ['title' => '.+'])]
    public function extract(GameSession $session, string $title): JsonResponse
    {
        try {
            $extractData = $this->wikipediaService->getPageExtract($title);

            return new JsonResponse([
                'success' => true,
                'title' => $extractData['title'],
                'extract' => $extractData['extract'],
                'thumbnail' => $extractData['thumbnail'],
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unable to fetch page preview',
            ], 404);
        }
    }

    #[Route('/{id}/finished', name: 'game_finished')]
    public function finished(GameSession $session): Response
    {
        if (!$session->isCompleted()) {
            return $this->redirectToRoute('game_start', [
                'id' => $session->getChallenge()->getId()
            ]);
        }

        $statistics = $this->navigationService->calculateStatistics($session);

        return $this->render('game/finished.html.twig', [
            'session' => $session,
            'challenge' => $session->getChallenge(),
            'statistics' => $statistics,
        ]);
    }
}
