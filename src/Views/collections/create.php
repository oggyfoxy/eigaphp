<!-- src/Views/collections/create.php -->
<h1><?= htmlspecialchars($pageTitle ?? 'Create Collection') ?></h1>

<?php if (!empty($flashMessages)): ?>
  <div class="flash-messages">
    <?php foreach ($flashMessages as $msg): ?>
      <div class="flash-message flash-<?= $msg['type'] ?>">
        <?= htmlspecialchars($msg['message']) ?>
        <button class="close-btn">&times;</button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="form-errors">
    <ul>
      <?php foreach ($errors as $field => $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/collection/create" method="post" class="mt-20">
  <div class="form-group">
    <label for="title">Title</label>
    <input
      type="text"
      id="title"
      name="title"
      value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
      required
    >
  </div>

  <div class="form-group mt-10">
    <label for="description">Description</label>
    <textarea
      id="description"
      name="description"
      rows="4"
    ><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
  </div>

  <div class="form-group mt-10">
    <label>
      <input
        type="checkbox"
        name="is_private"
        <?= !empty($formData['is_private']) ? 'checked' : '' ?>
      >
      Private
    </label>
  </div>

  <div class="mt-20">
    <button type="submit" class="btn">Create</button>
    <a href="<?= BASE_URL ?>/profile" class="btn btn-outline ml-10">Cancel</a>
  </div>
</form>
