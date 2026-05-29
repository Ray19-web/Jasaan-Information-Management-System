document.addEventListener("DOMContentLoaded", () => {
    const accountInput = document.getElementById("accountSearch");
    const accountFilterButtons = Array.from(document.querySelectorAll("[data-account-role-filter]"));
    const accountResetButton = document.getElementById("accountFilterReset");
    const accountResultCount = document.getElementById("accountResultCount");
    const accountActiveFilter = document.getElementById("accountActiveFilter");
    const accountsEmptyState = document.getElementById("accountsEmptyState");

    function setActiveAccountFilter(nextButton) {
        accountFilterButtons.forEach((button) => {
            const isActive = button === nextButton;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function getActiveAccountRoleValue() {
        const activeButton = accountFilterButtons.find((button) => button.classList.contains("is-active"));
        return activeButton?.dataset.accountRoleFilter || "";
    }

    function getActiveAccountRoleLabel() {
        const activeButton = accountFilterButtons.find((button) => button.classList.contains("is-active"));
        return activeButton?.textContent?.trim() || "All roles";
    }

    function filterAccountRows() {
        const query = accountInput ? accountInput.value.toLowerCase().trim() : "";
        const selectedRole = getActiveAccountRoleValue();
        const rows = Array.from(document.querySelectorAll("#accountsTable tr[data-role]"));
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowText = (row.dataset.search || row.innerText).toLowerCase();
            const rowRole = row.dataset.role || "";
            const matchesSearch = !query || rowText.includes(query);
            const matchesRole = !selectedRole || rowRole === selectedRole;
            const isVisible = matchesSearch && matchesRole;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        const hasActiveFilters = Boolean(query || selectedRole);
        const selectedLabel = getActiveAccountRoleLabel();

        if (accountResetButton) {
            accountResetButton.hidden = !hasActiveFilters;
        }

        if (accountResultCount) {
            if (!rows.length) {
                accountResultCount.textContent = "No accounts available";
            } else if (visibleCount === rows.length && !hasActiveFilters) {
                accountResultCount.textContent = `Showing all ${rows.length} accounts`;
            } else {
                accountResultCount.textContent = `${visibleCount} account${visibleCount === 1 ? "" : "s"} visible`;
            }
        }

        if (accountActiveFilter) {
            if (!hasActiveFilters) {
                accountActiveFilter.textContent = "All roles";
            } else if (query && selectedRole) {
                accountActiveFilter.textContent = `${selectedLabel} - "${query}"`;
            } else if (selectedRole) {
                accountActiveFilter.textContent = selectedLabel;
            } else {
                accountActiveFilter.textContent = `Search: "${query}"`;
            }
        }

        if (accountsEmptyState) {
            accountsEmptyState.hidden = visibleCount > 0;
        }
    }

    if (accountInput) {
        accountInput.addEventListener("input", filterAccountRows);
    }

    accountFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveAccountFilter(button);
            filterAccountRows();
        });
    });

    if (accountResetButton) {
        accountResetButton.addEventListener("click", () => {
            if (accountInput) {
                accountInput.value = "";
            }

            const allButton = accountFilterButtons.find((button) => (button.dataset.accountRoleFilter || "") === "");
            if (allButton) {
                setActiveAccountFilter(allButton);
            }

            filterAccountRows();
            accountInput?.focus();
        });
    }

    if (accountInput || accountFilterButtons.length > 0) {
        filterAccountRows();
    }
});

function updateRole(userId, role, selectEl, oldRole) {
    return fetch("/jasaan-tourism/backend/update_role.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ user_id: userId, role: role })
    })
        .then(res => res.json())
        .then(data => {
            window.showToast?.(data.message, data.status);

            if (data.status === "success") {
                selectEl?.setAttribute("data-old", role);
                setTimeout(() => location.reload(), 800);
                return;
            }

            if (selectEl && oldRole) {
                selectEl.value = oldRole;
            }
        })
        .catch(() => {
            if (selectEl && oldRole) {
                selectEl.value = oldRole;
            }
            window.showToast?.("Failed to update role", "error");
        });
}

function confirmDeleteUser(id) {
    window.openConfirmModal?.(
        "Delete User",
        "Move this user to the Recycle Bin? You can restore the account later.",
        () => deleteUser(id)
    );
}

function deleteUser(id) {
    return fetch(`/jasaan-tourism/backend/delete_user.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            window.showToast?.(data.message, data.status);
            if (data.status === "success") {
                setTimeout(() => location.reload(), 800);
            }
        })
        .catch(() => {
            window.showToast?.("Delete failed", "error");
        });
}

function confirmRoleChange(userId, selectEl) {
    const newRole = selectEl.value;
    const oldRole = selectEl.getAttribute("data-old") || newRole;

    window.openConfirmModal?.(
        "Change Role",
        `Are you sure you want to change role to "${newRole}"?`,
        () => updateRole(userId, newRole, selectEl, oldRole),
        {
            onCancel: () => {
                selectEl.value = oldRole;
            }
        }
    );
}
