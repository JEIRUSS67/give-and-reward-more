<?php
declare(strict_types=1);

define('GARM_ADMIN_PAGE', 'messages');

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();
garm_require_login();

$pageTitle = 'Messages & Requests';
$db = garm_db();
$formError = '';
$formSuccess = '';

$allowedContactStatuses = ['new', 'read', 'archived'];
$allowedSupportStatuses = ['new', 'in_progress', 'resolved'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!garm_csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'update_contact_status') {
            $status = $_POST['status'] ?? '';
            if (in_array($status, $allowedContactStatuses, true)) {
                $stmt = $db->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
                $stmt->execute([':status' => $status, ':id' => $id]);
                $formSuccess = 'Message status updated.';
            }
        } elseif ($action === 'update_support_status') {
            $status = $_POST['status'] ?? '';
            if (in_array($status, $allowedSupportStatuses, true)) {
                $stmt = $db->prepare('UPDATE support_requests SET status = :status WHERE id = :id');
                $stmt->execute([':status' => $status, ':id' => $id]);
                $formSuccess = 'Support request status updated.';
            }
        } elseif ($action === 'delete_contact') {
            $stmt = $db->prepare('DELETE FROM contact_messages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $formSuccess = 'Message deleted.';
        } elseif ($action === 'delete_support') {
            $stmt = $db->prepare('DELETE FROM support_requests WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $formSuccess = 'Support request deleted.';
        }
    }
}

$contactMessages = $db->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100')->fetchAll();
$supportRequests = $db->query('SELECT * FROM support_requests ORDER BY created_at DESC LIMIT 100')->fetchAll();
$csrfToken = garm_csrf_token();

function garm_status_badge(string $status): string
{
    $map = [
        'new' => 'badge--new',
        'in_progress' => 'badge--progress',
        'resolved' => 'badge--resolved',
        'read' => 'badge--progress',
        'archived' => 'badge--resolved',
    ];
    $class = $map[$status] ?? 'badge--draft';
    return '<span class="badge ' . $class . '">' . garm_escape(str_replace('_', ' ', ucfirst($status))) . '</span>';
}

require __DIR__ . '/_header.php';
?>

<div class="admin-topbar">
  <div>
    <h1 style="font-size: var(--step-2);">Messages &amp; Requests</h1>
    <p style="color: var(--color-charcoal-soft); margin: 0;">Review contact messages and support requests submitted through the website.</p>
  </div>
</div>

<?php if ($formError !== ''): ?><div class="form-status form-status--error is-visible"><?= garm_escape($formError) ?></div><?php endif; ?>
<?php if ($formSuccess !== ''): ?><div class="form-status form-status--success is-visible"><?= garm_escape($formSuccess) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom: var(--space-4);">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">Support Requests</h3>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Type</th><th>Contact</th><th>Message</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($supportRequests)): ?>
          <tr><td colspan="7">No support requests yet.</td></tr>
        <?php else: foreach ($supportRequests as $req): ?>
          <tr>
            <td><?= garm_escape($req['full_name']) ?><br><span style="color:var(--color-charcoal-soft); font-size:0.85em;"><?= garm_escape($req['country']) ?></span></td>
            <td><?= garm_escape($req['support_type']) ?></td>
            <td><?= garm_escape($req['email']) ?><?= $req['phone'] ? '<br>' . garm_escape($req['phone']) : '' ?></td>
            <td style="max-width: 22em;"><?= nl2br(garm_escape($req['message'])) ?></td>
            <td><?= garm_status_badge($req['status']) ?></td>
            <td><?= garm_escape($req['created_at']) ?></td>
            <td class="admin-actions">
              <form method="post" style="display:flex; gap:0.4em; flex-wrap:wrap;">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="update_support_status">
                <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach ($allowedSupportStatuses as $status): ?>
                    <option value="<?= $status ?>" <?= $req['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$status)) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <form method="post" onsubmit="return confirm('Delete this request?');">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_support">
                <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                <button type="submit" class="btn btn--outline btn--sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-card">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">Contact Messages</h3>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Contact</th><th>Subject</th><th>Message</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($contactMessages)): ?>
          <tr><td colspan="7">No messages yet.</td></tr>
        <?php else: foreach ($contactMessages as $msg): ?>
          <tr>
            <td><?= garm_escape($msg['full_name']) ?></td>
            <td><?= garm_escape($msg['email']) ?><?= $msg['phone'] ? '<br>' . garm_escape($msg['phone']) : '' ?></td>
            <td><?= garm_escape($msg['subject']) ?></td>
            <td style="max-width: 22em;"><?= nl2br(garm_escape($msg['message'])) ?></td>
            <td><?= garm_status_badge($msg['status']) ?></td>
            <td><?= garm_escape($msg['created_at']) ?></td>
            <td class="admin-actions">
              <form method="post" style="display:flex; gap:0.4em; flex-wrap:wrap;">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="update_contact_status">
                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach ($allowedContactStatuses as $status): ?>
                    <option value="<?= $status ?>" <?= $msg['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <form method="post" onsubmit="return confirm('Delete this message?');">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_contact">
                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                <button type="submit" class="btn btn--outline btn--sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
