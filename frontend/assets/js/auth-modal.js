





















document.addEventListener("DOMContentLoaded", () => {

    
    const loginModal = document.getElementById("loginModal");
    const signupModal = document.getElementById("signupModal");
    const forgotModal = document.getElementById("forgotModal");

    
    

    const closeLogin = document.getElementById("closeModal");
    const closeSignup = document.getElementById("closeSignup");
    const closeForgot = document.getElementById("closeForgot");

    
    const openSignup = document.getElementById("openSignup");
    const openLogin = document.getElementById("openLogin");
    const openForgot = document.getElementById("openForgot");
    const backToLogin = document.getElementById("backToLogin");

    
    function closeAll() {
        loginModal.classList.remove("show");
        signupModal.classList.remove("show");
        forgotModal.classList.remove("show");
    }

    function openModal(modal) {
        closeAll();
        modal.classList.add("show");
    }



    
    closeLogin?.addEventListener("click", closeAll);
    closeSignup?.addEventListener("click", closeAll);
    closeForgot?.addEventListener("click", closeAll);

    
    openSignup?.addEventListener("click", (e) => {
        e.preventDefault();
        openModal(signupModal);
    });

    openLogin?.addEventListener("click", (e) => {
        e.preventDefault();
        openModal(loginModal);
    });

    openForgot?.addEventListener("click", (e) => {
        e.preventDefault();
        openModal(forgotModal);
    });

    backToLogin?.addEventListener("click", (e) => {
        e.preventDefault();
        openModal(loginModal);
    });

    
    window.addEventListener("click", (e) => {
        if (e.target === loginModal || 
            e.target === signupModal || 
            e.target === forgotModal) {
            closeAll();
        }
    });

    
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeAll();
        }
    });

});