(() => {
    function isElementBusy(control) {
        return Boolean(control?.dataset?.lockBusy === "true");
    }

    function rememberDefaultLabel(control) {
        if (!control || control.dataset.lockDefaultHtml) {
            return;
        }

        control.dataset.lockDefaultHtml = control.innerHTML;
        control.dataset.lockDefaultText = control.textContent.trim();
    }

    function prime(control, idleText) {
        if (!control) {
            return;
        }

        if (typeof idleText === "string") {
            control.textContent = idleText;
        }

        control.disabled = false;
        control.dataset.lockBusy = "false";
        control.classList.remove("is-busy");
        control.removeAttribute("aria-busy");
        control.removeAttribute("data-busy-label");
        control.dataset.lockDefaultHtml = control.innerHTML;
        control.dataset.lockDefaultText = control.textContent.trim();
    }

    function setBusy(control, busy, options = {}) {
        if (!control) {
            return;
        }

        rememberDefaultLabel(control);

        if (busy) {
            const busyText = options.busyText || control.dataset.busyText || "Please wait...";
            control.disabled = true;
            control.dataset.lockBusy = "true";
            control.dataset.busyLabel = busyText;
            control.classList.add("is-busy");
            control.setAttribute("aria-busy", "true");
            control.textContent = busyText;
            return;
        }

        control.disabled = false;
        control.dataset.lockBusy = "false";
        control.classList.remove("is-busy");
        control.removeAttribute("aria-busy");
        control.removeAttribute("data-busy-label");

        if (typeof options.idleText === "string") {
            control.textContent = options.idleText;
            control.dataset.lockDefaultHtml = control.innerHTML;
            control.dataset.lockDefaultText = control.textContent.trim();
            return;
        }

        if (control.dataset.lockDefaultHtml) {
            control.innerHTML = control.dataset.lockDefaultHtml;
        }
    }

    async function withLock(control, action, options = {}) {
        if (!control) {
            return action();
        }

        if (isElementBusy(control)) {
            return null;
        }

        setBusy(control, true, options);

        let succeeded = false;

        try {
            const result = await action();
            succeeded = true;
            return result;
        } finally {
            if (!succeeded || !options.keepBusyOnSuccess) {
                setBusy(control, false, options);
            }
        }
    }

    window.ActionLock = {
        isBusy: isElementBusy,
        prime,
        setBusy,
        withLock
    };
})();
