document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = window.BASE_URL || "/jasaan-tourism";
    const buttons = Array.from(document.querySelectorAll("[data-recycle-action]"));
    const searchInput = document.getElementById("recycleSearch");
    const typeFilterButtons = Array.from(document.querySelectorAll("[data-recycle-filter]"));
    const dateFilter = document.getElementById("recycleDateFilter");
    const resetButton = document.getElementById("recycleFilterReset");
    const resultCount = document.getElementById("recycleResultCount");
    const activeFilter = document.getElementById("recycleActiveFilter");
    const rows = Array.from(document.querySelectorAll("[data-recycle-row]"));
    const panels = Array.from(document.querySelectorAll("[data-recycle-panel]"));

    function titleCase(value) {
        return String(value || "item")
            .replace(/_/g, " ")
            .replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    function setActiveTypeFilter(nextButton) {
        typeFilterButtons.forEach((button) => {
            const isActive = button === nextButton;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function getActiveTypeFilter() {
        return typeFilterButtons.find((button) => button.classList.contains("is-active"))?.dataset.recycleFilter || "";
    }

    function getActiveTypeLabel() {
        return typeFilterButtons.find((button) => button.classList.contains("is-active"))?.textContent?.trim() || "All";
    }

    function parseDeletedDate(value) {
        if (!value) return null;
        const parsed = new Date(String(value).replace(" ", "T"));
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function matchesDateFilter(rowDate, selectedDateFilter) {
        if (!selectedDateFilter) return true;
        if (!rowDate) return false;

        const now = new Date();

        if (selectedDateFilter === "today") {
            return rowDate.getFullYear() === now.getFullYear()
                && rowDate.getMonth() === now.getMonth()
                && rowDate.getDate() === now.getDate();
        }

        const days = Number(selectedDateFilter);
        if (!Number.isFinite(days)) return true;

        const cutoff = new Date(now);
        cutoff.setDate(cutoff.getDate() - days);
        return rowDate >= cutoff;
    }

    function getDateFilterLabel(value) {
        if (value === "today") return "Today";
        if (value === "7") return "Last 7 days";
        if (value === "30") return "Last 30 days";
        return "Any time";
    }

    function applyRecycleFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const selectedType = getActiveTypeFilter();
        const selectedDate = dateFilter?.value || "";
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowText = `${row.dataset.search || ""} ${row.innerText || ""}`.toLowerCase();
            const rowDate = parseDeletedDate(row.dataset.deletedAt);
            const matchesSearch = !query || rowText.includes(query);
            const matchesType = !selectedType || row.dataset.itemType === selectedType;
            const matchesDate = matchesDateFilter(rowDate, selectedDate);
            const isVisible = matchesSearch && matchesType && matchesDate;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        panels.forEach((panel) => {
            const panelType = panel.dataset.recyclePanel || "";
            const panelRows = rows.filter((row) => row.closest("[data-recycle-panel]") === panel);
            const visiblePanelRows = panelRows.filter((row) => !row.hidden);
            const filterEmpty = panel.querySelector("[data-recycle-filter-empty]");

            panel.hidden = Boolean(selectedType && panelType !== selectedType);

            if (filterEmpty) {
                filterEmpty.hidden = panel.hidden || panelRows.length === 0 || visiblePanelRows.length > 0;
            }
        });

        const hasActiveFilters = Boolean(query || selectedType || selectedDate);

        if (resetButton) {
            resetButton.hidden = !hasActiveFilters;
        }

        if (resultCount) {
            if (!rows.length) {
                resultCount.textContent = "No deleted records available";
            } else if (!hasActiveFilters) {
                resultCount.textContent = `Showing all ${rows.length} deleted item${rows.length === 1 ? "" : "s"}`;
            } else {
                resultCount.textContent = `${visibleCount} deleted item${visibleCount === 1 ? "" : "s"} visible`;
            }
        }

        if (activeFilter) {
            const labels = [];

            if (selectedType) labels.push(getActiveTypeLabel());
            if (selectedDate) labels.push(getDateFilterLabel(selectedDate));
            if (query) labels.push(`Search: "${query}"`);

            activeFilter.textContent = labels.length ? labels.join(" - ") : "All deleted records";
        }
    }

    searchInput?.addEventListener("input", applyRecycleFilters);
    dateFilter?.addEventListener("change", applyRecycleFilters);

    typeFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveTypeFilter(button);
            applyRecycleFilters();
        });
    });

    resetButton?.addEventListener("click", () => {
        if (searchInput) {
            searchInput.value = "";
        }

        if (dateFilter) {
            dateFilter.value = "";
        }

        const allButton = typeFilterButtons.find((button) => (button.dataset.recycleFilter || "") === "");
        if (allButton) {
            setActiveTypeFilter(allButton);
        }

        applyRecycleFilters();
        searchInput?.focus();
    });

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            const action = button.dataset.recycleAction;
            const itemType = button.dataset.itemType;
            const itemId = button.dataset.itemId;
            const isRestore = action === "restore";
            const itemLabel = titleCase(itemType);
            const modalTitle = isRestore ? `Restore ${itemLabel}` : `Permanently Delete ${itemLabel}`;
            const message = isRestore
                ? `Restore this ${itemLabel.toLowerCase()} to the admin records?`
                : `This will permanently delete this ${itemLabel.toLowerCase()}. This cannot be undone.`;

            const runAction = async () => {
                if (window.ActionLock?.isBusy(button)) {
                    return;
                }

                window.ActionLock?.setBusy(button, true, {
                    busyText: isRestore ? "Restoring..." : "Deleting..."
                });

                const formData = new FormData();
                formData.append("item_type", itemType);
                formData.append("item_id", itemId);
                formData.append("action", action);

                try {
                    const response = await fetch(`${baseUrl}/backend/recycle_bin_action.php`, {
                        method: "POST",
                        body: formData
                    });
                    const data = await response.json();

                    if (!response.ok || data.status !== "success") {
                        throw new Error(data.message || "Recycle Bin action failed.");
                    }

                    window.showToast?.(data.message, "success");
                    setTimeout(() => location.reload(), 700);
                } catch (error) {
                    window.showToast?.(error.message || "Recycle Bin action failed.", "error");
                    window.ActionLock?.setBusy(button, false);
                }
            };

            if (typeof window.openConfirmModal === "function") {
                window.openConfirmModal(modalTitle, message, runAction);
            } else if (confirm(message)) {
                runAction();
            }
        });
    });

    applyRecycleFilters();
});
