document.addEventListener("DOMContentLoaded", () => {
    // Each public asset page has its own grid, search box, filter chips, and show-more button.
    document.querySelectorAll("[data-asset-grid-root]").forEach((root) => {
        const input = root.querySelector("[data-asset-search]");
        const grid = root.querySelector("[data-asset-grid]");
        const cards = Array.from(root.querySelectorAll(".jt-asset-card"));
        const showMoreWrap = root.querySelector("[data-show-more-wrap]");
        const showMoreBtn = root.querySelector("[data-show-more-btn]");
        const staticEmptyState = root.querySelector("[data-static-empty-state]");
        const filterButtons = Array.from(root.querySelectorAll("[data-user-asset-filter]"));
        const initialLimit = Number.parseInt(root.dataset.initialLimit || "8", 10);
        const emptyMessage = root.dataset.emptyMessage || "No results found.";

        if (!grid || cards.length === 0) {
            return;
        }

        let expanded = false;
        let activeFilter = "";

        function removeSearchEmptyState() {
            grid.querySelectorAll("[data-search-empty-state]").forEach((element) => element.remove());
        }

        function ensureSearchEmptyState() {
            removeSearchEmptyState();

            const emptyState = document.createElement("p");
            emptyState.className = "jt-no-results";
            emptyState.dataset.searchEmptyState = "true";
            emptyState.innerText = emptyMessage;
            grid.appendChild(emptyState);
        }

        function updateShowMoreVisibility(visibleCount, hasFilters) {
            if (!showMoreWrap) {
                return;
            }

            const shouldShow = !hasFilters && !expanded && cards.length > initialLimit && visibleCount >= initialLimit;
            showMoreWrap.style.display = shouldShow ? "block" : "none";
        }

        function render(searchValue = "") {
            // Search and filter happen in the browser by hiding/showing cards that are already loaded.
            const normalizedSearch = searchValue.toLowerCase().trim();
            const hasFilters = normalizedSearch !== "" || activeFilter !== "";
            let visibleCount = 0;
            let matchedIndex = 0;

            removeSearchEmptyState();

            cards.forEach((card) => {
                const searchableText = [
                    card.dataset.name || "",
                    card.dataset.location || "",
                    card.dataset.type || "",
                    card.dataset.status || ""
                ].join(" ");
                const typeSlugs = (card.dataset.typeSlugs || "").split(/\s+/).filter(Boolean);

                const matchesSearch = normalizedSearch === "" || searchableText.includes(normalizedSearch);
                const matchesFilter = activeFilter === "" || typeSlugs.includes(activeFilter);
                const matches = matchesSearch && matchesFilter;
                const shouldShow = !hasFilters
                    ? (expanded || matchedIndex < initialLimit)
                    : matchesSearch;

                card.style.display = shouldShow && matches ? "block" : "none";

                if (matches) {
                    matchedIndex++;
                }

                if (card.style.display !== "none") {
                    visibleCount++;
                }
            });

            updateShowMoreVisibility(visibleCount, hasFilters);

            if (staticEmptyState) {
                staticEmptyState.style.display = hasFilters ? "none" : "";
            }

            if (hasFilters && visibleCount === 0) {
                ensureSearchEmptyState();
            }
        }

        input?.addEventListener("keyup", function () {
            render(this.value);
        });

        filterButtons.forEach((button) => {
            button.addEventListener("click", () => {
                activeFilter = button.dataset.userAssetFilter || "";
                expanded = activeFilter !== "" ? true : expanded;

                filterButtons.forEach((item) => {
                    const isActive = item === button;
                    item.classList.toggle("is-active", isActive);
                    item.setAttribute("aria-pressed", isActive ? "true" : "false");
                });

                render(input?.value || "");
            });
        });

        showMoreBtn?.addEventListener("click", () => {
            expanded = true;
            render(input?.value || "");
        });

        render();
    });
});
