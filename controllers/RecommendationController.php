<?php
/**
 * Campus Market Hub - Recommendation Engine Controller & API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Recommendation;
use App\Services\RecommendationEngine;

class RecommendationController extends Controller {
    /**
     * API: Get recommendations for current user
     */
    public function getRecommendations(): void {
        Auth::requireLogin();
        $limit = max(1, min(20, (int)$this->input('limit', 6)));

        $recommendations = Recommendation::getForUser(Auth::id(), $limit);
        $this->json([
            'success' => true,
            'count'   => count($recommendations),
            'data'    => $recommendations
        ]);
    }

    /**
     * Force re-computation of recommendations
     */
    public function recompute(): void {
        Auth::requireLogin();
        $engine = new RecommendationEngine();
        $results = $engine->computeForUser(Auth::id());

        if ($this->isAjax()) {
            $this->json(['success' => true, 'updated' => count($results), 'data' => $results]);
        }

        $this->setFlash('success', 'Your personalized campus recommendations have been updated!');
        $this->redirect('/profile');
    }
}
