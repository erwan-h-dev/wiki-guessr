<?php

namespace App\Service;

use App\Entity\GameSession;
use Doctrine\ORM\EntityManagerInterface;

class GameNavigationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Handle navigation to a new page
     *
     * @return bool True if this is a back navigation, false otherwise
     */
    public function navigateToPage(GameSession $session, string $pageTitle): bool
    {
        $path = $session->getPath();
        $isBackNavigation = false;

        // Check if this page was already in the path (back navigation)
        $existingIndex = array_search($pageTitle, $path);

        if ($existingIndex !== false) {
            // This is a back navigation
            $isBackNavigation = true;

            // Get the last page before going back
            $fromPage = end($path);

            // Truncate path to the back-navigation point
            $session->setPath(array_slice($path, 0, $existingIndex + 1));

            // Add back navigation event
            $session->addBackNavigation($fromPage, $pageTitle);
        } else {
            // Normal forward navigation
            $path[] = $pageTitle;
            $session->setPath($path);

            // Add page visit event
            $session->addPageVisit($pageTitle);
        }

        $this->entityManager->flush();

        return $isBackNavigation;
    }

    /**
     * Check if the current page is the target page
     */
    public function isTargetReached(GameSession $session, string $currentPage): bool
    {
        return $currentPage === $session->getChallenge()->getEndPage();
    }

    /**
     * Calculate statistics for a completed game session
     */
    public function calculateStatistics(GameSession $session): array
    {
        $events = $session->getEvents();
        $path = $session->getPath();

        $totalPagesVisited = count($path);
        $totalEvents = count($events);
        $backNavigations = array_filter($events, fn($event) => $event['type'] === 'back_navigation');
        $backNavigationCount = count($backNavigations);

        // Calculate dead ends (pages visited but not in the final path)
        $allVisitedPages = [];
        foreach ($events as $event) {
            if ($event['type'] === 'page_visit') {
                $allVisitedPages[] = $event['page'];
            }
        }

        $deadEnds = array_diff($allVisitedPages, $path);
        $deadEndCount = count($deadEnds);

        // Calculate efficiency (ratio of successful path to total exploration)
        $totalExploredPages = count(array_unique($allVisitedPages));
        $efficiency = $totalExploredPages > 0 ? ($totalPagesVisited / $totalExploredPages) * 100 : 100;

        return [
            'duration' => $session->getDurationSeconds(),
            'pathLength' => $totalPagesVisited,
            'path' => $path,
            'totalEvents' => $totalEvents,
            'backNavigationCount' => $backNavigationCount,
            'deadEndCount' => $deadEndCount,
            'deadEnds' => array_values($deadEnds),
            'efficiency' => round($efficiency, 2),
        ];
    }
}
