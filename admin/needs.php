<?php
declare(strict_types=1);

define('GARM_ADMIN_PAGE', 'needs');

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();
garm_require_login();

$pageTitle = 'What We Need';
$db = garm_db();
$formError = '';
$formSuccess = '';
$editingNeed = null;

// ---- Handle form submissions (create / update / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!garm_csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM needs WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $formSuccess = 'Need item deleted.';
        } elseif ($action === 'save') {
            $id          = (int) ($_POST['id'] ?? 0);
            $title       = garm_clean_string($_POST['title'] ?? '', 150);
            $description = garm_clean_string($_POST['description'] ?? '', 1000);
            $iconKey     = garm_clean_string($_POST['icon_key'] ?? '', 60);
            $priority    = (int) ($_POST['priority_order'] ?? 0);
            $isPublished = isset($_POST['is_published']) ? 1 : 0;

            if ($title === '' || $description === '') {
                $formError = 'Title and description are required.';
            } else {
                if ($id > 0) {
                    $stmt = $db->prepare(
                        'UPDATE needs SET title = :title, description = :description, icon_key = :icon_key,
                         priority_order = :priority_order, is_published = :is_published WHERE id = :id'
                    );
                    $stmt->execute([
                        ':title' => $title, ':description' => $description, ':icon_key' => $iconKey,
                        ':priority_order' => $priority, ':is_published' => $isPublished, ':id' => $id,
                    ]);
                    $formSuccess = 'Need item updated.';
                } else {
                    $stmt = $db->prepare(
                        'INSERT INTO needs (title, description, icon_key, priority_order, is_published, created_by, created_at)
                         VALUES (:title, :description, :icon_key, :priority_order, :is_published, :created_by, NOW())'
                    );
                    $stmt->execute([
                        ':title' => $title, ':description' => $description, ':icon_key' => $iconKey,
                        ':priority_order' => $priority, ':is_published' => $isPublished,
                        ':created_by' => $_SESSION['admin_user_id'],
                    ]);
                    $formSuccess = 'Need item added.';
                }
            }
        }
    }
}

// ---- Load a record for editing ----
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM needs WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editingNeed = $stmt->fetch() ?: null;
}

$needs = $db->query('SELECT * FROM needs ORDER BY priority_order ASC, created_at DESC')->fetchAll();
$csrfToken = garm_csrf_token();

require __DIR__ . '/_header.php';
?>

<div class="admin-topbar">
  <div>
    <h1 style="font-size: var(--step-2);">What We Need</h1>
    <p style="color: var(--color-charcoal-soft); margin: 0;">Manage the need categories shown on the public "What We Need" page.</p>
  </div>
</div>

<?php if ($formError !== ''): ?><div class="form-status form-status--error is-visible"><?= garm_escape($formError) ?></div><?php endif; ?>
<?php if ($formSuccess !== ''): ?><div class="form-status form-status--success is-visible"><?= garm_escape($formSuccess) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom: var(--space-4);">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);"><?= $editingNeed ? 'Edit Need' : 'Add a Need' ?></h3>
  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editingNeed['id'] ?? 0) ?>">
    <div class="form-grid form-grid--two">
      <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= garm_escape($editingNeed['title'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="icon_key">Icon key (optional)</label>
        <input type="text" id="icon_key" name="icon_key" placeholder="e.g. shelter, food, health" value="<?= garm_escape($editingNeed['icon_key'] ?? '') ?>">
      </div>
      <div class="field field--full">
        <label for="description">Description</label>
        <textarea id="description" name="description" required><?= garm_escape($editingNeed['description'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label for="priority_order">Priority order (lower shows first)</label>
        <input type="number" id="priority_order" name="priority_order" min="0" value="<?= (int) ($editingNeed['priority_order'] ?? 0) ?>">
      </div>
      <div class="field">
        <label for="is_published">Status</label>
        <label style="display:flex; align-items:center; gap:0.5em; font-weight:400;">
          <input type="checkbox" id="is_published" name="is_published" style="width:auto;" <?= !empty($editingNeed['is_published']) ? 'checked' : '' ?>>
          Published on the public site
        </label>
      </div>
    </div>
    <p style="margin-top: var(--space-3);">
      <button type="submit" class="btn btn--primary"><?= $editingNeed ? 'Save Changes' : 'Add Need' ?></button>
      <?php if ($editingNeed): ?><a class="btn btn--outline" href="needs.php">Cancel</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="admin-card">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">Current Needs</h3>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Priority</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($needs)): ?>
          <tr><td colspan="4">No need items yet.</td></tr>
        <?php else: foreach ($needs as $need): ?>
          <tr>
            <td><?= (int) $need['priority_order'] ?></td>
            <td><?= garm_escape($need['title']) ?></td>
            <td><span class="badge <?= $need['is_published'] ? 'badge--published' : 'badge--draft' ?>"><?= $need['is_published'] ? 'Published' : 'Draft' ?></span></td>
            <td class="admin-actions">
              <a class="btn btn--outline btn--sm" href="?edit=<?= (int) $need['id'] ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this need item?');">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $need['id'] ?>">
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
