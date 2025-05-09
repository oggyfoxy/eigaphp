<!-- src/Views/users/edit.php -->
<h1>Edit Profile</h1>

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

<form action="<?= BASE_URL ?>/profile/edit" method="post" enctype="multipart/form-data" class="mt-20">
  <div class="form-group">
    <label for="username">Username</label>
    <input
      type="text"
      id="username"
      name="username"
      value="<?= htmlspecialchars($user['username'] ?? '') ?>"
      required
    >
  </div>

  <div class="form-group mt-10">
    <label for="email">Email</label>
    <input
      type="email"
      id="email"
      name="email"
      value="<?= htmlspecialchars($user['email'] ?? '') ?>"
      required
    >
  </div>

  <div class="form-group mt-10">
    <label for="avatar">Avatar</label>
    <?php if (!empty($user['avatar'])): ?>
      <div class="mt-5">
        <img
          src="<?= htmlspecialchars($user['avatar']) ?>"
          alt="Current avatar"
          style="max-width:100px; border-radius:4px;"
        >
      </div>
    <?php endif; ?>
    <input type="file" id="avatar" name="avatar" accept="image/*" class="mt-5">
  </div>

  <div class="mt-20">
    <button type="submit" class="btn">Save Changes</button>
    <a href="<?= BASE_URL ?>/profile/change-password" class="btn btn-outline ml-10">
      Change Password
    </a>
  </div>
</form>

<p class="mt-20">
  <a href="<?= BASE_URL ?>/profile">← Back to Profile</a>
</p>
