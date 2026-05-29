<?php
require_once __DIR__ . "/../../backend/db.php";
require_once __DIR__ . "/../../backend/optional_session.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

if (!isset($_GET['id'])) {
    exit;
}

$asset_id = intval($_GET['id']);

$stmt = $conn->prepare(
    "SELECT a.*,
            s.status_code AS asset_status,
            GROUP_CONCAT(DISTINCT t.type_id ORDER BY t.type_name SEPARATOR ',') AS type_ids,
            GROUP_CONCAT(DISTINCT t.type_name ORDER BY t.type_name SEPARATOR ', ') AS type_name,
            ati.transportation,
            ati.nearby_stay,
            ati.travel_tips,
            ati.estimated_cost,
            ati.travel_time,
            ati.best_time,
            ati.difficulty
     FROM assets a
     LEFT JOIN asset_type_assignments ata ON ata.asset_id = a.asset_id
     LEFT JOIN asset_types t ON ata.type_id = t.type_id AND t.deleted_at IS NULL
     LEFT JOIN asset_statuses s ON s.status_id = a.status_id
     LEFT JOIN asset_travel_info ati ON ati.asset_id = a.asset_id
     WHERE a.asset_id = ?
       AND a.deleted_at IS NULL
     GROUP BY a.asset_id"
);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$asset) {
    exit;
}

$images = [];
if (!empty($asset['thumbnail'])) {
    $images[] = $asset['thumbnail'];
}

$imgStmt = $conn->prepare("SELECT image_path FROM asset_images WHERE asset_id = ? ORDER BY image_id ASC");
$imgStmt->bind_param("i", $asset_id);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
while ($row = $imgRes->fetch_assoc()) {
    if (!empty($row['image_path']) && !in_array($row['image_path'], $images, true)) {
        $images[] = $row['image_path'];
    }
}
$imgStmt->close();

if (count($images) === 0) {
    $images[] = '';
}

$socials = [];
$socialStmt = $conn->prepare(
    "SELECT sp.platform_code AS platform, asl.url
     FROM asset_social_links asl
     JOIN social_platforms sp ON sp.platform_id = asl.platform_id
     WHERE asl.asset_id = ?"
);
$socialStmt->bind_param("i", $asset_id);
$socialStmt->execute();
$socialRes = $socialStmt->get_result();
while ($row = $socialRes->fetch_assoc()) {
    $socials[] = $row;
}
$socialStmt->close();

$feedbacks = [];
$feedbackStmt = $conn->prepare(
    "SELECT f.*, u.full_name, u.profile_picture
     FROM feedbacks f
     LEFT JOIN users u ON f.user_id = u.user_id
     WHERE f.asset_id = ?
       AND f.is_hidden = 0
       AND f.deleted_at IS NULL
       AND u.deleted_at IS NULL
     ORDER BY f.created_at DESC"
);
$feedbackStmt->bind_param("i", $asset_id);
$feedbackStmt->execute();
$feedbackRes = $feedbackStmt->get_result();

$total = 0;
$count = 0;
while ($row = $feedbackRes->fetch_assoc()) {
    $feedbacks[] = $row;
    $total += $row['rating'];
    $count++;
}
$feedbackStmt->close();

$avg = $count ? number_format($total / $count, 1) : "0.0";
$typeNames = array_values(array_filter(array_map('trim', explode(',', (string) $asset['type_name']))));
if ($typeNames === []) {
    $typeNames = ['Unclassified'];
}
$assetStatuses = [
    'open' => ['label' => 'Open', 'icon' => 'fa-circle-check'],
    'temporarily_closed' => ['label' => 'Temporarily Closed', 'icon' => 'fa-clock'],
    'permanently_closed' => ['label' => 'Permanently Closed', 'icon' => 'fa-circle-xmark'],
    'abandoned' => ['label' => 'Abandoned', 'icon' => 'fa-triangle-exclamation'],
    'under_renovation' => ['label' => 'Under Renovation', 'icon' => 'fa-screwdriver-wrench'],
];
$assetStatusValue = $asset['asset_status'] ?: 'open';
$assetStatus = $assetStatuses[$assetStatusValue] ?? $assetStatuses['open'];

function typeBadgeClass(string $typeName): string
{
    return strtolower(preg_replace('/\s+/', '_', trim($typeName)));
}

function modalText($value, $fallback)
{
    $text = trim((string) $value);
    $safe = $text === '' ? $fallback : $text;
    return nl2br(htmlspecialchars($safe));
}

function socialIcon(string $platform): string
{
    return match (strtolower($platform)) {
        'facebook' => 'fa-facebook',
        'instagram' => 'fa-instagram',
        'twitter' => 'fa-twitter',
        'x' => 'fa-x-twitter',
        'youtube' => 'fa-youtube',
        'linkedin' => 'fa-linkedin',
        'tiktok' => 'fa-tiktok',
        default => 'fa-globe'
    };
}
?>

<div class="jtam-overlay" onclick="jtamClose(event)">
    <div class="jtam-modal">
        <div class="jtam-close" onclick="jtamRemove()" role="button" aria-label="Close modal" tabindex="0">X</div>

        <div class="jtam-container">
            <div class="jtam-left">
                <div class="jtam-carousel" id="jtamCarousel">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="jtam-slide <?= $index === 0 ? 'jtam-active' : '' ?>">
                            <img src="<?= $img !== '' ? $BASE_URL . '/uploads/' . htmlspecialchars($img) : $BASE_URL . '/frontend/assets/images/default.png' ?>"
                                alt="<?= htmlspecialchars($asset['asset_name']) ?>" />
                        </div>
                    <?php endforeach; ?>

                    <button class="jtam-nav jtam-prev" type="button">&#8249;</button>
                    <button class="jtam-nav jtam-next" type="button">&#8250;</button>
                </div>

                <div class="jtam-title">
                    <div class="jt-type-badge-list">
                        <?php foreach ($typeNames as $typeName): ?>
                            <span class="jt-type-badge <?= htmlspecialchars(typeBadgeClass($typeName)) ?>">
                                <?= htmlspecialchars(strtoupper($typeName)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="jtam-status-notice jtam-status-notice--<?= htmlspecialchars($assetStatusValue) ?>">
                        <div class="jtam-status-notice__main">
                            <i class="fa-solid <?= htmlspecialchars($assetStatus['icon']) ?>"></i>
                            <span><?= htmlspecialchars($assetStatus['label']) ?></span>
                        </div>
                        <?php if (!empty($asset['status_note'])): ?>
                            <p><?= htmlspecialchars($asset['status_note']) ?></p>
                        <?php endif; ?>
                    </div>
                    <h2><?= htmlspecialchars($asset['asset_name']) ?></h2>
                    <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($asset['location']) ?></p>
                </div>
            </div>

            <div class="jtam-right">
                <section class="jtam-panel jtam-panel--overview">
                    <div class="jtam-section-heading">
                        <span class="jtam-section-kicker">Place Overview</span>
                        <h3>Discover The Place</h3>
                    </div>
                    <p class="jtam-copy"><?= modalText($asset['description'], 'No description added yet.') ?></p>
                </section>

                <section class="jtam-panel">
                    <div class="jtam-section-heading">
                        <span class="jtam-section-kicker">Trip Planning</span>
                        <h3>Travel Info</h3>
                    </div>

                    <div class="jtam-info-grid">
                        <article class="jtam-info-card jtam-info-card--wide">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-route"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Transportation</span>
                                <p class="jtam-info-card__value"><?= modalText($asset['transportation'], 'Route guidance will be added soon.') ?></p>
                            </div>
                        </article>

                        <article class="jtam-info-card">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-wallet"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Estimated Cost</span>
                                <p class="jtam-info-card__value"><?= htmlspecialchars($asset['estimated_cost'] ?: 'Ask locally for updated fares.') ?></p>
                            </div>
                        </article>

                        <article class="jtam-info-card">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-clock"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Travel Time</span>
                                <p class="jtam-info-card__value"><?= htmlspecialchars($asset['travel_time'] ?: 'Travel time has not been listed yet.') ?></p>
                            </div>
                        </article>

                        <article class="jtam-info-card jtam-info-card--wide">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-bed"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Nearby Stay</span>
                                <p class="jtam-info-card__value"><?= modalText($asset['nearby_stay'], 'Nearby hotel or lodging details have not been added yet.') ?></p>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="jtam-panel">
                    <div class="jtam-section-heading">
                        <span class="jtam-section-kicker">Before You Go</span>
                        <h3>Visitor Info</h3>
                    </div>

                    <div class="jtam-info-grid">
                        <article class="jtam-info-card jtam-info-card--wide">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-lightbulb"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Tips</span>
                                <p class="jtam-info-card__value"><?= modalText($asset['travel_tips'], 'No visitor tips added yet.') ?></p>
                            </div>
                        </article>

                        <article class="jtam-info-card">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-sun"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Best Time</span>
                                <p class="jtam-info-card__value"><?= htmlspecialchars($asset['best_time'] ?: 'Best time is not specified yet.') ?></p>
                            </div>
                        </article>

                        <article class="jtam-info-card">
                            <div class="jtam-info-card__icon"><i class="fa-solid fa-person-hiking"></i></div>
                            <div class="jtam-info-card__body">
                                <span class="jtam-info-card__label">Difficulty</span>
                                <p class="jtam-info-card__value"><?= htmlspecialchars($asset['difficulty'] ?: 'Difficulty level is not specified yet.') ?></p>
                            </div>
                        </article>
                    </div>
                </section>

                <div class="jtam-meta-grid">
                    <section class="jtam-contact-card">
                        <div class="jtam-section-heading jtam-section-heading--compact">
                            <span class="jtam-section-kicker">Stay Connected</span>
                            <h3>Contact</h3>
                        </div>

                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <div class="contact-copy">
                                <span>Phone</span>
                                <?php if (!empty($asset['phone_number'])): ?>
                                    <a href="tel:<?= htmlspecialchars($asset['phone_number']) ?>"><?= htmlspecialchars($asset['phone_number']) ?></a>
                                <?php else: ?>
                                    <span class="jtam-empty">Not available yet</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <div class="contact-copy">
                                <span>Email</span>
                                <?php if (!empty($asset['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($asset['email']) ?>"><?= htmlspecialchars($asset['email']) ?></a>
                                <?php else: ?>
                                    <span class="jtam-empty">Not available yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="jtam-contact-card">
                        <div class="jtam-section-heading jtam-section-heading--compact">
                            <span class="jtam-section-kicker">Online Presence</span>
                            <h3>Social Media</h3>
                        </div>

                        <div class="jtam-socials">
                            <?php if (count($socials) === 0): ?>
                                <p class="jtam-no-social">No social links added yet.</p>
                            <?php else: ?>
                                <?php foreach ($socials as $social): ?>
                                    <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands <?= socialIcon($social['platform']) ?>"></i>
                                        <span><?= htmlspecialchars(ucfirst($social['platform'])) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <section class="jtam-map-card">
                    <div class="jtam-section-heading jtam-section-heading--compact">
                        <span class="jtam-section-kicker">Navigate</span>
                        <h3>Map Preview</h3>
                    </div>
                    <div id="jtamMap" data-lat="<?= htmlspecialchars((string) $asset['latitude']) ?>"
                        data-lng="<?= htmlspecialchars((string) $asset['longitude']) ?>"></div>
                </section>

                <section class="jtam-feedback-section">
                    <div class="jtam-feedback-head">
                        <div class="jtam-section-heading jtam-section-heading--compact">
                            <span class="jtam-section-kicker">Community Voices</span>
                            <h3>Feedback</h3>
                        </div>
                        <div class="jtam-feedback-score">
                            <strong id="jtamAvg"><?= $avg ?></strong>
                            <span>&#9733;</span>
                        </div>
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <p class="jtam-login">Login to leave feedback.</p>
                    <?php else: ?>
                        <div class="jtam-feedback-form">
                            <textarea id="jtamText" placeholder="Share your experience..."></textarea>
                            <div class="jtam-form-bottom">
                                <div class="jtam-stars" data-role="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span data-rate="<?= $i ?>">&#9733;</span>
                                    <?php endfor; ?>
                                </div>
                                <button onclick="jtamSubmit(<?= $asset_id ?>, this)" class="jtam-submit-btn" type="button">
                                    Submit
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="jtamFeedbackList">
                        <?php if (count($feedbacks) === 0): ?>
                            <p class="jtam-empty-state">No feedback yet. Be the first to share your experience.</p>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <div class="jtam-feedback">
                                    <div class="jtam-feedback-header">
                                        <div class="left">
                                            <img src="<?= $BASE_URL ?>/<?= htmlspecialchars($feedback['profile_picture'] ?? 'uploads/profile_pictures/default-user.jpg') ?>"
                                                alt="Profile picture" class="jtam-profile-pic">
                                            <span class="jtam-name"><?= htmlspecialchars($feedback['full_name']) ?></span>
                                        </div>
                                        <div class="jtam-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="<?= $i <= $feedback['rating'] ? 'active' : '' ?>">&#9733;</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="jtam-comment"><?= nl2br(htmlspecialchars($feedback['comment'])) ?></div>
                                    <hr style="border: #06b5d439 solid 0.1px; margin: 10px 0;">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
