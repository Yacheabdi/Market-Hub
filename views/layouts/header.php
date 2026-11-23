<?php
/**
 * Campus Market Hub - Header & Navigation Layout
 */
use App\Core\Auth;
use App\Models\Message;

$unreadCount = Auth::check() ? Message::getUnreadCount(Auth::id()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Campus Market Hub - University Marketplace') ?></title>
    <!-- Modern Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <!-- Top University Trust Bar -->
    <div class="top-trust-bar">
        <div class="container trust-bar-content">
            <div class="trust-pill">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Official Campus Network: Exclusively for Verified Students & Staff</span>
            </div>
            <div class="trust-links">
                <span><i class="fa-solid fa-location-dot"></i> Safe Campus Meetup Zones: Library & Student Center</span>
            </div>
        </div>
    </div>

    <!-- Main Header Navbar -->
    <header class="main-header">
        <div class="container navbar-container">
            <!-- Brand Logo -->
            <a href="/" class="brand-logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-title">Campus<span class="highlight">Market</span></span>
                    <span class="logo-tag">Hub & Exchanges</span>
                </div>
            </a>

            <!-- Quick Search Bar (Desktop) -->
            <form action="/" method="GET" class="header-search-form">
                <div class="search-input-group">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="q" placeholder="Search textbooks, electronics, dorm furniture..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>

            <!-- Navigation Actions -->
            <nav class="nav-actions">
                <a href="/" class="nav-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/') ? 'active' : '' ?>">
                    <i class="fa-solid fa-compass"></i>
                    <span>Explore</span>
                </a>

                <?php if (Auth::check()): ?>
                    <a href="/products/create" class="nav-btn-post">
                        <i class="fa-solid fa-plus"></i>
                        <span>Sell / Donate</span>
                    </a>

                    <a href="/messages" class="nav-link icon-link message-icon-link <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/messages')) ? 'active' : '' ?>" title="Campus Messages">
                        <i class="fa-solid fa-comments"></i>
                        <span>Chat</span>
                        <span class="badge-count unread-badge <?= $unreadCount > 0 ? '' : 'hidden' ?>"><?= $unreadCount ?></span>
                    </a>

                    <a href="/transactions" class="nav-link <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/transactions')) ? 'active' : '' ?>" title="Transactions">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Trades</span>
                    </a>

                    <!-- User Dropdown Pill -->
                    <div class="user-dropdown">
                        <button class="user-pill-btn" id="userMenuBtn">
                            <div class="user-avatar-small">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div class="user-pill-meta">
                                <span class="user-pill-name"><?= htmlspecialchars(explode(' ', Auth::user()['FullName'])[0]) ?></span>
                                <span class="user-role-badge role-<?= strtolower(Auth::user()['Role']) ?>"><?= Auth::user()['Role'] ?></span>
                            </div>
                            <i class="fa-solid fa-chevron-down caret-icon"></i>
                        </button>

                        <div class="dropdown-menu" id="userMenuDropdown">
                            <div class="dropdown-header">
                                <div class="dropdown-user-name"><?= htmlspecialchars(Auth::user()['FullName']) ?></div>
                                <div class="dropdown-user-id">ID: <?= htmlspecialchars(Auth::user()['Registration_StaffNo']) ?></div>
                                <?php if (Auth::isVerified()): ?>
                                    <span class="verified-pill"><i class="fa-solid fa-circle-check"></i> Verified Campus Member</span>
                                <?php else: ?>
                                    <span class="unverified-pill"><i class="fa-solid fa-clock"></i> Verification Pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="/profile" class="dropdown-item"><i class="fa-solid fa-user-gear"></i> My Profile & Settings</a>
                            <a href="/products/manage" class="dropdown-item"><i class="fa-solid fa-boxes-stacked"></i> My Listings</a>
                            <a href="/transactions" class="dropdown-item"><i class="fa-solid fa-handshake-simple"></i> My Trades & Claims</a>
                            <?php if (Auth::isAdmin()): ?>
                                <div class="dropdown-divider"></div>
                                <a href="/admin" class="dropdown-item admin-link"><i class="fa-solid fa-shield-virus"></i> Admin Fraud Desk</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/logout" class="dropdown-item text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
                        </div>
                    </div>

                <?php else: ?>
                    <a href="/login" class="nav-link"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</a>
                    <a href="/register" class="btn-primary-sm">Register Account</a>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle -->
                <button class="mobile-toggle-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </nav>
        </div>

        <!-- Mobile Drawer Navigation -->
        <div class="mobile-nav-drawer" id="mobileDrawer">
            <div class="mobile-drawer-header">
                <span class="drawer-title">Campus Market Hub</span>
                <button class="drawer-close-btn" id="closeDrawerBtn"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="/" method="GET" class="mobile-search-form">
                <input type="text" name="q" placeholder="Search marketplace..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <div class="mobile-nav-links">
                <a href="/"><i class="fa-solid fa-house"></i> Marketplace Home</a>
                <?php if (Auth::check()): ?>
                    <a href="/products/create"><i class="fa-solid fa-plus"></i> Post an Item / Service</a>
                    <a href="/products/manage"><i class="fa-solid fa-boxes-stacked"></i> My Campus Listings</a>
                    <a href="/messages"><i class="fa-solid fa-comments"></i> Chat Messages (<?= $unreadCount ?>)</a>
                    <a href="/transactions"><i class="fa-solid fa-receipt"></i> Transactions Tracker</a>
                    <a href="/profile"><i class="fa-solid fa-user"></i> My Profile</a>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="/admin" class="admin-link"><i class="fa-solid fa-shield"></i> Admin Control Panel</a>
                    <?php endif; ?>
                    <a href="/logout" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
                <?php else: ?>
                    <a href="/login"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</a>
                    <a href="/register"><i class="fa-solid fa-user-plus"></i> Register University Account</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Global Flash Notification Alerts -->
    <?php if (!empty($flashes)): ?>
        <div class="container flash-notifications">
            <?php foreach ($flashes as $type => $message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible">
                    <div class="alert-icon">
                        <?php if ($type === 'success'): ?>
                            <i class="fa-solid fa-circle-check"></i>
                        <?php elseif ($type === 'error'): ?>
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php elseif ($type === 'warning'): ?>
                            <i class="fa-solid fa-circle-exclamation"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-circle-info"></i>
                        <?php endif; ?>
                    </div>
                    <div class="alert-text"><?= $message ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main class="main-content">
