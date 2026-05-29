document.addEventListener("DOMContentLoaded", () => {
    const assetInput = document.getElementById("searchInput");
    const assetFilterButtons = Array.from(document.querySelectorAll("[data-asset-filter]"));
    const resetFiltersButton = document.getElementById("assetFilterReset");
    const assetResultCount = document.getElementById("assetResultCount");
    const assetActiveFilter = document.getElementById("assetActiveFilter");
    const assetsEmptyState = document.getElementById("assetsEmptyState");

    function setActiveAssetFilter(nextButton) {
        assetFilterButtons.forEach((button) => {
            const isActive = button === nextButton;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function getActiveAssetFilterValue() {
        const activeButton = assetFilterButtons.find((button) => button.classList.contains("is-active"));
        return activeButton?.dataset.assetFilter || "";
    }

    function getActiveAssetFilterLabel() {
        const activeButton = assetFilterButtons.find((button) => button.classList.contains("is-active"));
        return activeButton?.textContent?.trim() || "All classifications";
    }

    const filterAssetRows = () => {
        const query = assetInput ? assetInput.value.toLowerCase().trim() : "";
        const selectedType = getActiveAssetFilterValue();
        const rows = Array.from(document.querySelectorAll("#assetsTable tr[data-type]"));
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowText = (row.dataset.search || row.innerText).toLowerCase();
            const rowTypes = (row.dataset.type || "").split(/\s+/).filter(Boolean);
            const matchesSearch = !query || rowText.includes(query);
            const matchesType = !selectedType || rowTypes.includes(selectedType);
            const isVisible = matchesSearch && matchesType;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        const hasActiveFilters = Boolean(query || selectedType);
        const selectedLabel = getActiveAssetFilterLabel();

        if (resetFiltersButton) {
            resetFiltersButton.hidden = !hasActiveFilters;
        }

        if (assetResultCount) {
            if (!rows.length) {
                assetResultCount.textContent = "No assets available";
            } else if (visibleCount === rows.length && !hasActiveFilters) {
                assetResultCount.textContent = `Showing all ${rows.length} assets`;
            } else {
                assetResultCount.textContent = `${visibleCount} asset${visibleCount === 1 ? "" : "s"} visible`;
            }
        }

        if (assetActiveFilter) {
            if (!hasActiveFilters) {
                assetActiveFilter.textContent = "All classifications";
            } else if (query && selectedType) {
                assetActiveFilter.textContent = `${selectedLabel} - "${query}"`;
            } else if (selectedType) {
                assetActiveFilter.textContent = selectedLabel;
            } else {
                assetActiveFilter.textContent = `Search: "${query}"`;
            }
        }

        if (assetsEmptyState) {
            assetsEmptyState.hidden = visibleCount > 0;
        }
    };

    if (assetInput) {
        assetInput.addEventListener("input", filterAssetRows);
    }

    assetFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveAssetFilter(button);
            filterAssetRows();
        });
    });

    if (resetFiltersButton) {
        resetFiltersButton.addEventListener("click", () => {
            if (assetInput) {
                assetInput.value = "";
            }

            const allButton = assetFilterButtons.find((button) => (button.dataset.assetFilter || "") === "");
            if (allButton) {
                setActiveAssetFilter(allButton);
            }

            filterAssetRows();
            assetInput?.focus();
        });
    }

    if (assetInput || assetFilterButtons.length > 0) {
        filterAssetRows();
    }
});
