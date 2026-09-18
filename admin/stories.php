<?php
declare(strict_types=1);

define('GARM_ADMIN_PAGE', 'stories');

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();
garm_require_login();

$pageTitle = 'Stories & Updates';
$db = garm_db();
$formError = '';
$formSuccess = '';
$editingStory = null;

function garm_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!garm_csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM stories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $formSuccess = 'Story deleted.';
        } elseif ($action === 'save') {
            $id          = (int) ($_POST['id'] ?? 0);
            $title       = garm_clean_string($_POST['title'] ?? '', 200);
            $category    = garm_clean_string($_POST['category'] ?? '', 100);
            $summary     = garm_clean_string($_POST['summary'] ?? '', 400);
            $body        = garm_clean_string($_POST['body'] ?? '', 8000);
            $isPublished = isset($_POST['is_published']) ? 1 : 0;

            if ($title === '' || $summary === '' || $body === '') {
                $formError = 'Title, summary, and body are required.';
            } else {
                $slug = garm_slugify($title) ?: ('story-' . time());

                if ($id > 0) {
                    $stmt = $db->prepare(
                        'UPDATE stories SET title = :title, slug = :slug, category = :category, summary = :summary,
                         body = :body, is_published = :is_published,
                         published_at = CASE WHEN :is_published2 = 1 AND published_at IS NULL THEN NOW() ELSE published_at END
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':title' => $title, ':slug' => $slug, ':category' => $category, ':summary' => $summary,
                        ':body' => $body, ':is_published' => $isPublished, ':is_published2' => $isPublished, ':id' => $id,
                    ]);
                    $formSuccess = 'Story updated.';
                } else {
                    $stmt = $db->prepare(
                        'INSERT INTO stories (title, slug, category, summary, body, is_published, published_at, created_by, created_at)
                         VALUES (:title, :slug, :category, :summary, :body, :is_published, :published_at, :created_by, NOW())'
                    );
                    $stmt->execute([
                        ':title' => $title, ':slug' => $slug, ':category' => $category, ':summary' => $summary,
                        ':body' => $body, ':is_published' => $isPublished,
                        ':published_at' => $isPublished ? date('Y-m-d H:i:s') : null,
                        ':created_by' => $_SESSION['admin_user_id'],
                    ]);
                    $formSuccess = 'Story added.';
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM stories WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editingStory = $stmt->fetch() ?: null;
}

$stories = $db->query('SELECT * FROM stories ORDER BY created_at DESC')->fetchAll();
$csrfToken = garm_csrf_token();

require __DIR__ . '/_header.php';
?>

<div class="admin-topbar">
  <div>
    <h1 style="font-size: var(--step-2);">Stories &amp; Updates</h1>
    <p style="color: var(--color-charcoal-soft); margin: 0;">Publish updates, events, and stories to the public Stories &amp; Updates page.</p>
  </div>
</div>

<?php if ($formError !== ''): ?><div class="form-status form-status--error is-visible"><?= garm_escape($formError) ?></div><?php endif; ?>
<?php if ($formSuccess !== ''): ?><div class="form-status form-status--success is-visible"><?= garm_escape($formSuccess) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom: var(--space-4);">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);"><?= $editingStory ? 'Edit Story' : 'Add a Story' ?></h3>
  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editingStory['id'] ?? 0) ?>">
    <div class="form-grid form-grid--two">
      <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= garm_escape($editingStory['title'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="category">Category</label>
        <input type="text" id="category" name="category" placeholder="e.g. Education, Events" value="<?= garm_escape($editingStory['category'] ?? '') ?>">
      </div>
      <div class="field field--full">
        <label for="summary">Short summary</label>
        <input type="text" id="summary" name="summary" required value="<?= garm_escape($editingStory['summary'] ?? '') ?>">
      </div>
      <div class="field field--full">
        <label for="body">Full story</label>
        <textarea id="body" name="body" required style="min-height: 12rem;"><?= garm_escape($editingStory['body'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label style="display:flex; align-items:center; gap:0.5em; font-weight:400;">
          <input type="checkbox" id="is_published" name="is_published" style="width:auto;" <?= !empty($editingStory['is_published']) ? 'checked' : '' ?>>
          Published on the public site
        </label>
      </div>
    </div>
    <p style="margin-top: var(--space-3);">
      <button type="submit" class="btn btn--primary"><?= $editingStory ? 'Save Changes' : 'Add Story' ?></button>
      <?php if ($editingStory): ?><a class="btn btn--outline" href="stories.php">Cancel</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="admin-card">
  <h3 style="font-size: var(--step-1); margin-bottom: var(--space-2);">All Stories</h3>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($stories)): ?>
          <tr><td colspan="4">No stories yet.</td></tr>
        <?php else: foreach ($stories as $story): ?>
          <tr>
            <td><?= garm_escape($story['title']) ?></td>
            <td><?= garm_escape($story['category']) ?></td>
            <td><span class="badge <?= $story['is_published'] ? 'badge--published' : 'badge--draft' ?>"><?= $story['is_published'] ? 'Published' : 'Draft' ?></span></td>
            <td class="admin-actions">
              <a class="btn btn--outline btn--sm" href="?edit=<?= (int) $story['id'] ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this story?');">
                <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $story['id'] ?>">
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
