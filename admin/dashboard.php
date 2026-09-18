<?php
declare(strict_types=1);

define('GARM_ADMIN_PAGE', 'dashboard');

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();
garm_require_login();

$pageTitle = 'Overview';

$counts = [
    'new_messages' => 0,
    'new_support'  => 0,
    'published_needs' => 0,
    'published_stories' => 0,
];

try {
    $db = garm_db();
    $counts['new_messages'] = (int) $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    $counts['new_support']  = (int) $db->query("SELECT COUNT(*) FROM support_requests WHERE status = 'new'")->fetchColumn();
    $counts['published_needs'] = (int) $db->query("SELECT COUNT(*) FROM needs WHERE is_published = 1")->fetchColumn();
    $counts['published_stories'] = (int) $db->query("SELECT COUNT(*) FROM stories WHERE is_published = 1")->fetchColumn();

    $recentSupport = $db->query(
        "SELECT full_name, support_type, created_at FROM support_requests ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    $recentMessages = $db->query(
        "SELECT full_name, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('[GARM] dashboard.php query failed: ' . $e->getMessage());
    $recentSupport = [];
    $recentMessages = [];
}

require __DIR__ . '/_header.php';
?>

<div class="admin-topbar">
  <div>
    <h1 style="font-size: var(--step-2);">Overview</h1>
    <p style="color: var(--color-charcoal-soft); margin: 0;">A quick summary of recent activity.</p>
  </div>
</div>

<div class="admin-stats">
  <div class="admin-card">
    <span class="stat__label">New contact messages</span>
    <span class="stat__number" style="font-size: var(--step-3);"><?= (int) $counts['new_messages'] ?></span>
  </div>
  <div class="admin-card">
    <span class="stat__label">New support requests</span>
    <span class="stat__number" style="font-size: var(--step-3);"><?= (int) $counts['new_support'] ?></span>
  </div>
  <div class="admin-card">
    <span class="stat__label">Published needs</span>
    <span class="stat__number" style="font-size: var(--step-3);"><?= (int) $counts['published_needs'] ?></span>
  </div>
  <div class="admin-card">
    <span class="stat__label">Published stories</span>
    <span class="stat__number" style="font-size: var(--step-3);"><?= (int) $counts['published_stories'] ?></span>
  </div>
</div>

<div class="split" style="align-items: start;">
  <div class="admin-card">
    <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">Recent Support Requests</h3>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Type</th><th>Received</th></tr></thead>
        <tbody>
          <?php if (empty($recentSupport)): ?>
            <tr><td colspan="3">No support requests yet.</td></tr>
          <?php else: foreach ($recentSupport as $row): ?>
            <tr>
              <td><?= garm_escape($row['full_name']) ?></td>
              <td><?= garm_escape($row['support_type']) ?></td>
              <td><?= garm_escape($row['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <p style="margin-top: var(--space-2);"><a href="messages.php">View all messages and requests</a></p>
  </div>

  <div class="admin-card">
    <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">Recent Contact Messages</h3>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Subject</th><th>Received</th></tr></thead>
        <tbody>
          <?php if (empty($recentMessages)): ?>
            <tr><td colspan="3">No messages yet.</td></tr>
          <?php else: foreach ($recentMessages as $row): ?>
            <tr>
              <td><?= garm_escape($row['full_name']) ?></td>
              <td><?= garm_escape($row['subject']) ?></td>
              <td><?= garm_escape($row['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <p style="margin-top: var(--space-2);"><a href="messages.php">View all messages and requests</a></p>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
