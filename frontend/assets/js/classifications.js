document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = window.BASE_URL || "/jasaan-tourism";
    const form = document.getElementById("classificationForm");
    const input = document.getElementById("classificationName");
    const table = document.getElementById("classificationTable");
    const resultCount = document.getElementById("classificationResultCount");
    const activeFilter = document.getElementById("classificationActiveFilter");
    const searchInput = document.getElementById("classificationSearch");
    const filterButtons = Array.from(document.querySelectorAll("[data-classification-filter]"));
    const resetButton = document.getElementById("classificationFilterReset");
    const emptyState = document.getElementById("classificationsEmptyState");
    const tones = ["cyan", "green", "gold", "rose", "violet", "blue"];

    function getRows() {
        return Array.from(table.querySelectorAll("tr[data-type-id]"));
    }

    function updateCount() {
        applyFilters();
    }

    function getActiveFilter() {
        return filterButtons.find((button) => button.classList.contains("is-active"))?.dataset.classificationFilter || "";
    }

    function getActiveFilterLabel() {
        return filterButtons.find((button) => button.classList.contains("is-active"))?.textContent?.trim() || "All classifications";
    }

    function setActiveFilter(button) {
        filterButtons.forEach((filterButton) => {
            const isActive = filterButton === button;
            filterButton.classList.toggle("is-active", isActive);
            filterButton.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function applyFilters() {
        const rows = getRows();
        const query = searchInput?.value.toLowerCase().trim() || "";
        const usageFilter = getActiveFilter();
        let visibleCount = 0;

        rows.forEach((row) => {
            const assetCount = Number(row.dataset.assetCount || "0");
            const matchesSearch = !query || (row.dataset.search || "").includes(query);
            const matchesUsage = !usageFilter || (usageFilter === "used" ? assetCount > 0 : assetCount === 0);
            const isVisible = matchesSearch && matchesUsage;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        const hasActiveFilters = Boolean(query || usageFilter);
        resultCount.textContent = `${visibleCount} classification${visibleCount === 1 ? "" : "s"} visible`;

        if (resetButton) {
            resetButton.hidden = !hasActiveFilters;
        }

        if (activeFilter) {
            const label = getActiveFilterLabel();
            if (!hasActiveFilters) {
                activeFilter.textContent = "All classifications";
            } else if (query && usageFilter) {
                activeFilter.textContent = `${label} - "${query}"`;
            } else if (usageFilter) {
                activeFilter.textContent = label;
            } else {
                activeFilter.textContent = `Search: "${query}"`;
            }
        }

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    }

    function postForm(url, values) {
        const formData = new FormData();
        Object.entries(values).forEach(([key, value]) => formData.append(key, value));

        return fetch(`${baseUrl}${url}`, {
            method: "POST",
            body: formData
        }).then((res) => res.json());
    }

    function escapeAttribute(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function createRow(type) {
        const row = document.createElement("tr");
        const tone = tones[Number(type.type_id) % tones.length];
        row.dataset.typeId = type.type_id;
        row.dataset.assetCount = "0";
        row.dataset.search = `${String(type.type_name).toLowerCase()} #${type.type_id}`;
        row.innerHTML = `
            <td data-label="Classification" style="text-align: left;">
                <div class="classification-name-cell">
                    <span class="classification-icon">
                        <i class="fa-solid fa-tag"></i>
                    </span>
                    <input
                        type="text"
                        class="classification-name-input"
                        value="${escapeAttribute(type.type_name)}"
                        data-original="${escapeAttribute(type.type_name)}"
                        maxlength="100"
                        title="Edit classification name">
                </div>
            </td>
            <td data-label="Assets Using It">
                <span class="classification-count-pill classification-count-pill--${tone}">0 assets</span>
            </td>
            <td data-label="Action" class="table-actions-cell">
                <i class="fa-solid fa-check action-icon edit" data-classification-save title="Save classification name"></i>
                <i class="fa-solid fa-trash action-icon delete" data-classification-delete title="Delete classification"></i>
            </td>
        `;
        return row;
    }

    form?.addEventListener("submit", (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector("button[type='submit']");
        const typeName = input.value.trim();

        if (!typeName || window.ActionLock?.isBusy(submitBtn)) {
            return;
        }

        window.ActionLock?.setBusy(submitBtn, true, { busyText: "Adding..." });

        postForm("/backend/save_asset_type.php", { type_name: typeName })
            .then((data) => {
                window.ActionLock?.setBusy(submitBtn, false, { idleText: "Add Type" });

                if (data.status !== "success") {
                    window.showToast?.(data.message || "Failed to save classification.", "error");
                    return;
                }

                const existingRow = table.querySelector(`tr[data-type-id="${data.data.type_id}"]`);
                if (!existingRow) {
                    table.insertBefore(createRow(data.data), emptyState);
                }

                input.value = "";
                updateCount();
                window.showToast?.(data.message, "success");
            })
            .catch(() => {
                window.ActionLock?.setBusy(submitBtn, false, { idleText: "Add Type" });
                window.showToast?.("Failed to save classification.", "error");
            });
    });

    table?.addEventListener("click", (event) => {
        const saveBtn = event.target.closest("[data-classification-save]");
        const deleteBtn = event.target.closest("[data-classification-delete]");
        const row = event.target.closest("tr[data-type-id]");

        if (!row) {
            return;
        }

        const typeId = row.dataset.typeId;
        const nameInput = row.querySelector(".classification-name-input");

        if (saveBtn) {
            const typeName = nameInput.value.trim();

            if (!typeName || saveBtn.disabled) {
                return;
            }

            saveBtn.disabled = true;

            postForm("/backend/update_asset_type.php", { type_id: typeId, type_name: typeName })
                .then((data) => {
                    saveBtn.disabled = false;

                    if (data.status !== "success") {
                        window.showToast?.(data.message || "Failed to update classification.", "error");
                        nameInput.value = nameInput.dataset.original || nameInput.value;
                        return;
                    }

                    nameInput.value = data.data.type_name;
                    nameInput.dataset.original = data.data.type_name;
                    row.dataset.search = `${String(data.data.type_name).toLowerCase()} #${typeId}`;
                    applyFilters();
                    window.showToast?.(data.message, "success");
                })
                .catch(() => {
                    saveBtn.disabled = false;
                    window.showToast?.("Failed to update classification.", "error");
                });
        }

        if (deleteBtn) {
            window.openConfirmModal?.(
                "Delete Classification",
                "Move this classification to the Recycle Bin? You can restore it later if no active asset needs the same name.",
                () => postForm("/backend/delete_asset_type.php", { type_id: typeId })
                    .then((data) => {
                        if (data.status !== "success") {
                            window.showToast?.(data.message || "Failed to delete classification.", "error");
                            return;
                        }

                        row.remove();
                        updateCount();
                        window.showToast?.(data.message, "success");
                    })
                    .catch(() => {
                        window.showToast?.("Failed to delete classification.", "error");
                    })
            );
        }
    });

    searchInput?.addEventListener("input", applyFilters);

    filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveFilter(button);
            applyFilters();
        });
    });

    resetButton?.addEventListener("click", () => {
        if (searchInput) {
            searchInput.value = "";
        }

        const allButton = filterButtons.find((button) => (button.dataset.classificationFilter || "") === "");
        if (allButton) {
            setActiveFilter(allButton);
        }

        applyFilters();
        searchInput?.focus();
    });

    applyFilters();
});
