function signup(event) {
    event.preventDefault();

    const form = document.getElementById("signupForm");
    const submitBtn = form?.querySelector(".login-btn");

    if (window.ActionLock?.isBusy(submitBtn)) {
        return;
    }

    window.ActionLock?.setBusy(submitBtn, true, { busyText: "Creating..." });

    const formData = new FormData(form);
    formData.set("full_name", document.getElementById("fullname").value);
    formData.set("email", document.getElementById("email").value);
    formData.set("username", document.getElementById("signupUsername").value);
    formData.set("password", document.getElementById("signupPassword").value);

    fetch("/jasaan-tourism/backend/signup.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            showToast(data.message, "success");
            
            form?.reset();
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "SIGN UP" });

            
            document.getElementById("signupModal").classList.remove("show");

            
            document.getElementById("loginModal").classList.add("show");
        } else {
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "SIGN UP" });
            showToast(data.message || "Signup failed", "error");
        }
    })
    .catch(() => {
        window.ActionLock?.setBusy(submitBtn, false, { idleText: "SIGN UP" });
        showToast("Something went wrong", "error");
    });
}






























