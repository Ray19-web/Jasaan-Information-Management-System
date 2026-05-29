document.getElementById("forgotForm")?.addEventListener("submit", function(e){
    e.preventDefault();

    const submitBtn = this.querySelector(".login-btn");
    if (window.ActionLock?.isBusy(submitBtn)) {
        return;
    }

    window.ActionLock?.setBusy(submitBtn, true, { busyText: "Saving..." });

    const formData = new FormData(this);

    fetch("/jasaan-tourism/backend/reset_password.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, "success");
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "SAVE CHANGES" });

            
            document.getElementById("forgotModal").classList.remove("show");
            document.getElementById("loginModal").classList.add("show");

        } else {
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "SAVE CHANGES" });
            showToast(data.message, "error");
        }
    })
    .catch(() => {
        window.ActionLock?.setBusy(submitBtn, false, { idleText: "SAVE CHANGES" });
        showToast("Error resetting password", "error");
    });
});
