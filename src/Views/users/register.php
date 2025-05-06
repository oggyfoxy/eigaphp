<!-- users/register.php view -->
<!-- File path: src/Views/users/register.php -->
<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Register</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <p>Please fix the errors below:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= BASE_URL ?>/register" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" 
                       value="<?= isset($formData['username']) ? htmlspecialchars($formData['username']) : '' ?>" required>
                <small class="form-text text-muted">Choose a unique username, 3-50 characters.</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" 
                       value="<?= isset($formData['email']) ? htmlspecialchars($formData['email']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
                <small class="form-text text-muted">At least 6 characters.</small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="<?= BASE_URL ?>/login">Login</a></p>
        </div>
    </div>
</div>