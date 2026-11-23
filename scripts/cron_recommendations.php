<?php
/**
 * Campus Market Hub - Background Recommendation Scoring Cron Job
 * Can be run via CLI: php scripts/cron_recommendations.php
 */

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Model.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Product.php';
require_once dirname(__DIR__) . '/models/Recommendation.php';
require_once dirname(__DIR__) . '/models/UserActivityLog.php';
require_once dirname(__DIR__) . '/services/RecommendationEngine.php';

use App\Services\RecommendationEngine;

echo "[" . date('Y-m-d H:i:s') . "] Starting Campus Market Hub Recommendation Engine...\n";

try {
    $engine = new RecommendationEngine();
    $processed = $engine->computeForAllUsers();
    echo "[" . date('Y-m-d H:i:s') . "] Successfully updated recommendations for {$processed} campus users.\n";
} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
