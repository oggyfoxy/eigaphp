<!-- users/login.php view -->
<!-- File path: src/Views/users/login.php -->
<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Login</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <?php if (isset($errors['login'])): ?>
                    <p><?= $errors['login'] ?></p>
                <?php else: ?>
                    <p>Please fix the errors below:</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= BASE_URL ?>/login" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" 
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">Remember me</label>
                </div>
                
                <a href="<?= BASE_URL ?>/forgot-password" class="text-primary">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="<?= BASE_URL ?>/register">Register</a></p>
        </div>
    </div>
</div>