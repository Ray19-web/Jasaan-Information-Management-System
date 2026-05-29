<div id="loginModal" class="modal">
    <div class="login-card">
        <div class="login-logo">
            <?php $BASE_URL = $BASE_URL ?? '/jasaan-tourism'; ?>
            <img src="<?= $BASE_URL ?>/frontend/assets/images/branding.png">
            <button class="modal-close" id="closeModal">&times;</button>
        </div>

        <form id="loginForm" onsubmit="login(event)" novalidate>
            <div class="title">LOGIN</div>

            <div class="input-group">
                <input id="loginUsername" type="text" name="username" placeholder="Username or Email..." required>
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="input-group">
                <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                <i class="fa-solid fa-eye-slash" id="toggleLoginPassword"></i>
            </div>

            <div class="options">
                <label><input type="checkbox" id="loginRemember" name="remember" value="1"> Remember Me</label>
                <a href="#" id="openForgot">Forgot Password?</a>
            </div>

            <button class="login-btn">LOGIN</button>
        </form>

        <div class="signup">
            Dont have an account. <a href="#" id="openSignup">Sign Up</a>
        </div>
    </div>
</div>

<div id="signupModal" class="modal">
    <div class="login-card">

        <div class="login-logo">
            <img src="<?= $BASE_URL ?>/frontend/assets/images/branding.png" alt="JASAYA LOGO">
            <button class="modal-close" id="closeSignup">&times;</button>
        </div>

        <div class="title title2">CREATE NEW ACCOUNT</div>

        <form id="signupForm" onsubmit="signup(event)">

            <div class="input-group">
                <input id="fullname" type="text" name="fullname" placeholder="Full name" required>
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="input-group">
                <input id="email" type="email" name="email" placeholder="Email Address" required>
                <i class="fa-solid fa-envelope"></i>
            </div>

            <div class="input-group">
                <input id="signupUsername" type="text" name="username" placeholder="Username" required>
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="input-group">
                <input type="password" id="signupPassword" name="password" placeholder="Enter your password" required>
                <i class="fa-solid fa-eye-slash" id="toggleSignupPassword"></i>
            </div>

            <button class="login-btn">SIGN UP</button>

        </form>

        <div class="signup">
            Already have an account? <a href="#" id="openLogin">Log In</a>
        </div>

    </div>
</div>

<div id="forgotModal" class="modal">
    <div class="login-card">

        <div class="login-logo">
            <img src="<?= $BASE_URL ?>/frontend/assets/images/branding.png" alt="JASAYA LOGO">
            <button class="modal-close" id="closeForgot">&times;</button>
        </div>

        <div class="title title3">FORGOT<br>PASSWORD</div>

        <form id="forgotForm">

            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required>
                <i class="fa-solid fa-envelope"></i>
            </div>

            <div class="input-group">
                <input type="password" id="newPassword" name="new_password" placeholder="New password" required>
                <i class="fa-solid fa-eye-slash" id="toggleNewPassword"></i>
            </div>

            <div class="input-group">
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm password"
                    required>
                <i class="fa-solid fa-eye-slash" id="toggleConfirmPassword"></i>
            </div>

            <button type="submit" class="login-btn">SAVE CHANGES</button>

        </form>

        <div class="signup">
            Already have an account? <a href="#" id="backToLogin">Log In</a>
        </div>

    </div>
</div>
