function escapeHTML(str) {
    return String(str || "").replace(/[&<>"']/g, function (match) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[match];
    });
}

function formatMultilineText(value, fallback) {
    const text = String(value || "").trim();
    const safeText = text === "" ? fallback : text;
    return escapeHTML(safeText).replace(/\r?\n/g, "<br>");
}

function showModalToast(message, type = "success") {
    if (typeof showToast === "function") {
        showToast(message, type);
        return;
    }

    alert(message);
}

function resolveAssetImage(imageName) {
    const BASE_URL = window.BASE_URL || "/jasaan-tourism";

    if (!imageName) {
        return `${BASE_URL}/frontend/assets/images/default.png`;
    }

    if (/^https?:\/\//i.test(imageName) || imageName.startsWith("/")) {
        return imageName;
    }

    return `${BASE_URL}/uploads/${imageName}`;
}

function getUniqueSlideImages(data) {
    const images = [];

    if (data.thumbnail) {
        images.push(data.thumbnail);
    }

    if (Array.isArray(data.images)) {
        data.images.forEach((img) => {
            if (img && !images.includes(img)) {
                images.push(img);
            }
        });
    }

    if (images.length === 0) {
        images.push("");
    }

    return images;
}

function buildInfoCard(config) {
    const classes = ["jtam-info-card"];

    if (config.wide) {
        classes.push("jtam-info-card--wide");
    }

    const valueMarkup = config.multiline
        ? `<p class="jtam-info-card__value">${formatMultilineText(config.value, config.fallback)}</p>`
        : `<p class="jtam-info-card__value">${escapeHTML((config.value || "").trim() || config.fallback)}</p>`;

    return `
        <article class="${classes.join(" ")}">
            <div class="jtam-info-card__icon">
                <i class="fa-solid ${config.icon}"></i>
            </div>
            <div class="jtam-info-card__body">
                <span class="jtam-info-card__label">${escapeHTML(config.label)}</span>
                ${valueMarkup}
            </div>
        </article>
    `;
}

function buildContactItem(iconClass, label, value, linkPrefix) {
    const text = String(value || "").trim();
    const valueMarkup = text
        ? `<a href="${linkPrefix}${escapeHTML(text)}">${escapeHTML(text)}</a>`
        : `<span class="jtam-empty">Not available yet</span>`;

    return `
        <div class="contact-item">
            <i class="fa-solid ${iconClass}"></i>
            <div class="contact-copy">
                <span>${escapeHTML(label)}</span>
                ${valueMarkup}
            </div>
        </div>
    `;
}

function buildSocialLinks(data) {
    const platforms = [
        { key: "facebook", icon: "fa-facebook", label: "Facebook" },
        { key: "instagram", icon: "fa-instagram", label: "Instagram" },
        { key: "twitter", icon: "fa-twitter", label: "Twitter" },
        { key: "tiktok", icon: "fa-tiktok", label: "TikTok" }
    ];

    const links = platforms
        .filter((platform) => data[platform.key])
        .map(
            (platform) => `
                <a href="${escapeHTML(data[platform.key])}" target="_blank" rel="noopener noreferrer">
                    <i class="fa-brands ${platform.icon}"></i>
                    <span>${platform.label}</span>
                </a>
            `
        )
        .join("");

    return links || '<p class="jtam-no-social">No social links added yet.</p>';
}

function buildFeedbackStars(rating) {
    return [1, 2, 3, 4, 5]
        .map((value) => `<span class="${value <= rating ? "active" : ""}">&#9733;</span>`)
        .join("");
}

function normalizeTypeNames(typeName) {
    const names = String(typeName || "")
        .split(",")
        .map((name) => name.trim())
        .filter(Boolean);

    return names.length ? names : ["Unclassified"];
}

function typeBadgeClass(typeName) {
    return String(typeName || "")
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "_");
}

function buildTypeBadges(typeName) {
    return normalizeTypeNames(typeName)
        .map((name) => `<span class="jt-type-badge ${typeBadgeClass(name)}">${escapeHTML(name.toUpperCase())}</span>`)
        .join("");
}

function getAssetStatusMeta(status) {
    const statuses = {
        open: { label: "Open", icon: "fa-circle-check" },
        temporarily_closed: { label: "Temporarily Closed", icon: "fa-clock" },
        permanently_closed: { label: "Permanently Closed", icon: "fa-circle-xmark" },
        abandoned: { label: "Abandoned", icon: "fa-triangle-exclamation" },
        under_renovation: { label: "Under Renovation", icon: "fa-screwdriver-wrench" }
    };

    return statuses[status] || statuses.open;
}

function buildStatusNotice(status, note) {
    const statusValue = status || "open";
    const meta = getAssetStatusMeta(statusValue);
    const noteText = String(note || "").trim();

    return `
        <div class="jtam-status-notice jtam-status-notice--${escapeHTML(statusValue)}">
            <div class="jtam-status-notice__main">
                <i class="fa-solid ${meta.icon}"></i>
                <span>${escapeHTML(meta.label)}</span>
            </div>
            ${noteText ? `<p>${escapeHTML(noteText)}</p>` : ""}
        </div>
    `;
}

function jtamOpen(id) {
    const BASE_URL = window.BASE_URL || "/jasaan-tourism";

    fetch(`${BASE_URL}/backend/get_asset.php?id=${id}`)
        .then((response) => response.json())
        .then((data) => {
            window.jtamRemove();

            const slideImages = getUniqueSlideImages(data);
            const isLoggedIn = Boolean(window.isLoggedIn);

            const imagesHtml = slideImages
                .map(
                    (img, index) => `
                        <div class="jtam-slide ${index === 0 ? "jtam-active" : ""}" data-index="${index}">
                            <img src="${resolveAssetImage(img)}" alt="${escapeHTML(data.asset_name || "Asset image")}" onclick="jtamOpenFullscreen(${index})" />
                        </div>
                    `
                )
                .join("");

            const modalHtml = `
                <div class="jtam-overlay" onclick="jtamClose(event)">
                    <div class="jtam-modal" data-asset-name="${escapeHTML(data.asset_name || "Unknown")}">
                        <div class="jtam-close" onclick="jtamRemove()" role="button" aria-label="Close modal" tabindex="0">X</div>
                        <div class="jtam-container">
                            <div class="jtam-left">
                                <div class="jtam-carousel" id="jtamCarousel">
                                    ${imagesHtml}
                                    <button class="jtam-nav jtam-prev" type="button">&#8249;</button>
                                    <button class="jtam-nav jtam-next" type="button">&#8250;</button>
                                </div>
                                <div class="jtam-title">
                                    <div class="jt-type-badge-list">${buildTypeBadges(data.type_name)}</div>
                                    ${buildStatusNotice(data.asset_status, data.status_note)}
                                    <h2>${escapeHTML(data.asset_name || "Unknown")}</h2>
                                    <p><i class="fa-solid fa-location-dot"></i> ${escapeHTML(data.location || "Location not available")}</p>
                                </div>
                            </div>

                            <div class="jtam-right">
                                <section class="jtam-panel jtam-panel--overview">
                                    <div class="jtam-section-heading">
                                        <span class="jtam-section-kicker">Place Overview</span>
                                        <h3>Discover The Place</h3>
                                    </div>
                                    <p class="jtam-copy">${formatMultilineText(data.description, "No description added yet.")}</p>
                                </section>

                                <section class="jtam-panel">
                                    <div class="jtam-section-heading">
                                        <span class="jtam-section-kicker">Trip Planning</span>
                                        <h3>Travel Info</h3>
                                    </div>

                                    <div class="jtam-info-grid">
                                        ${buildInfoCard({
                                            icon: "fa-route",
                                            label: "Transportation",
                                            value: data.transportation,
                                            fallback: "Route guidance will be added soon.",
                                            wide: true,
                                            multiline: true
                                        })}
                                        ${buildInfoCard({
                                            icon: "fa-wallet",
                                            label: "Estimated Cost",
                                            value: data.estimated_cost,
                                            fallback: "Ask locally for updated fares."
                                        })}
                                        ${buildInfoCard({
                                            icon: "fa-clock",
                                            label: "Travel Time",
                                            value: data.travel_time,
                                            fallback: "Travel time has not been listed yet."
                                        })}
                                        ${buildInfoCard({
                                            icon: "fa-bed",
                                            label: "Nearby Stay",
                                            value: data.nearby_stay,
                                            fallback: "Nearby hotel or lodging details have not been added yet.",
                                            wide: true,
                                            multiline: true
                                        })}
                                    </div>
                                </section>

                                <section class="jtam-panel">
                                    <div class="jtam-section-heading">
                                        <span class="jtam-section-kicker">Before You Go</span>
                                        <h3>Visitor Info</h3>
                                    </div>

                                    <div class="jtam-info-grid">
                                        ${buildInfoCard({
                                            icon: "fa-lightbulb",
                                            label: "Tips",
                                            value: data.travel_tips,
                                            fallback: "No visitor tips added yet.",
                                            wide: true,
                                            multiline: true
                                        })}
                                        ${buildInfoCard({
                                            icon: "fa-sun",
                                            label: "Best Time",
                                            value: data.best_time,
                                            fallback: "Best time is not specified yet."
                                        })}
                                        ${buildInfoCard({
                                            icon: "fa-person-hiking",
                                            label: "Difficulty",
                                            value: data.difficulty,
                                            fallback: "Difficulty level is not specified yet."
                                        })}
                                    </div>
                                </section>

                                <div class="jtam-meta-grid">
                                    <section class="jtam-contact-card">
                                        <div class="jtam-section-heading jtam-section-heading--compact">
                                            <span class="jtam-section-kicker">Stay Connected</span>
                                            <h3>Contact</h3>
                                        </div>
                                        ${buildContactItem("fa-phone", "Phone", data.phone_number, "tel:")}
                                        ${buildContactItem("fa-envelope", "Email", data.email, "mailto:")}
                                    </section>

                                    <section class="jtam-contact-card">
                                        <div class="jtam-section-heading jtam-section-heading--compact">
                                            <span class="jtam-section-kicker">Online Presence</span>
                                            <h3>Social Media</h3>
                                        </div>
                                        <div class="jtam-socials">
                                            ${buildSocialLinks(data)}
                                        </div>
                                    </section>
                                </div>

                                <section class="jtam-map-card">
                                    <div class="jtam-section-heading jtam-section-heading--compact">
                                        <span class="jtam-section-kicker">Navigate</span>
                                        <h3>Map Preview</h3>
                                    </div>
                                    <div id="jtamMap" data-lat="${escapeHTML(data.latitude || "")}" data-lng="${escapeHTML(data.longitude || "")}"></div>
                                </section>

                                <section class="jtam-feedback-section">
                                    <div class="jtam-feedback-head">
                                        <div class="jtam-section-heading jtam-section-heading--compact">
                                            <span class="jtam-section-kicker">Community Voices</span>
                                            <h3>Feedback</h3>
                                        </div>
                                        <div class="jtam-feedback-score">
                                            <strong id="jtamAvg">${Number(data.avg_rating || 0).toFixed(1)}</strong>
                                            <span>&#9733;</span>
                                        </div>
                                    </div>

                                    ${isLoggedIn ? `
                                        <div class="jtam-feedback-form">
                                            <textarea id="jtamText" placeholder="Share your experience..."></textarea>
                                            <div class="jtam-form-bottom">
                                                <div class="jtam-stars" data-role="rating-input">
                                                    <span data-rate="1">&#9733;</span>
                                                    <span data-rate="2">&#9733;</span>
                                                    <span data-rate="3">&#9733;</span>
                                                    <span data-rate="4">&#9733;</span>
                                                    <span data-rate="5">&#9733;</span>
                                                </div>
                                                <button onclick="jtamSubmit(${data.asset_id}, this)" class="jtam-submit-btn" type="button">Submit</button>
                                            </div>
                                        </div>
                                    ` : `<p class="jtam-login">Login to leave feedback.</p>`}

                                    <div id="jtamFeedbackList"></div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML("beforeend", modalHtml);
            setTimeout(() => jtamInit(data.asset_id), 50);
        })
        .catch((err) => {
            console.error("Failed to load asset:", err);
            showModalToast("Failed to load asset details.", "error");
        });
}

window.jtamRemove = function () {
    document.querySelector(".jtam-overlay")?.remove();
};

window.jtamClose = function (event) {
    if (event && event.target && event.target.classList.contains("jtam-overlay")) {
        window.jtamRemove();
    }
};

window.jtamOpenFullscreen = function (startIndex) {
    const modal = document.querySelector(".jtam-modal");

    if (!modal) {
        return;
    }

    const assetName = modal.dataset.assetName || "Asset";
    const slides = modal.querySelectorAll(".jtam-slide img");
    const images = Array.from(slides).map((slide) => slide.src);

    if (images.length === 0) {
        return;
    }

    const fullscreenHtml = `
        <div class="jtam-fullscreen-overlay" onclick="jtamCloseFullscreen(event)">
            <div class="jtam-fullscreen-container">
                <button class="jtam-fullscreen-close" onclick="jtamCloseFullscreen()" aria-label="Close fullscreen">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="jtam-fullscreen-main">
                    <img id="jtamFullscreenImg" src="${images[startIndex]}" alt="${escapeHTML(assetName)}" />

                    <button class="jtam-fullscreen-nav jtam-fullscreen-prev" onclick="jtamNavigateFullscreen(-1)" type="button">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button class="jtam-fullscreen-nav jtam-fullscreen-next" onclick="jtamNavigateFullscreen(1)" type="button">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <div class="jtam-fullscreen-thumbnails">
                    <div class="jtam-thumbnail-main">
                        <img src="${images[startIndex]}" alt="Preview" class="jtam-thumbnail-img" />
                        <div class="jtam-thumbnail-info">
                            <h3>${escapeHTML(assetName)}</h3>
                            <span class="jtam-image-counter">${startIndex + 1} / ${images.length}</span>
                        </div>
                    </div>

                    <div class="jtam-thumbnail-strip">
                        ${images.map((img, index) => `
                            <div class="jtam-thumbnail-item ${index === startIndex ? "active" : ""}" onclick="jtamGoToFullscreenImage(${index})">
                                <img src="${img}" alt="Image ${index + 1}" />
                            </div>
                        `).join("")}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML("beforeend", fullscreenHtml);

    window.jtamFullscreenState = {
        images,
        currentIndex: startIndex,
        assetName
    };

    jtamUpdateFullscreenNav();
    document.addEventListener("keydown", jtamHandleFullscreenKeydown);
};

function jtamHandleFullscreenKeydown(event) {
    if (!document.querySelector(".jtam-fullscreen-overlay")) {
        return;
    }

    if (event.key === "ArrowLeft") {
        event.preventDefault();
        jtamNavigateFullscreen(-1);
    }

    if (event.key === "ArrowRight") {
        event.preventDefault();
        jtamNavigateFullscreen(1);
    }

    if (event.key === "Escape") {
        event.preventDefault();
        jtamCloseFullscreen();
    }
}

function jtamCloseFullscreen(event) {
    if (!event || event.target.classList.contains("jtam-fullscreen-overlay")) {
        document.querySelector(".jtam-fullscreen-overlay")?.remove();
        document.removeEventListener("keydown", jtamHandleFullscreenKeydown);
    }
}

function jtamNavigateFullscreen(direction) {
    if (!window.jtamFullscreenState) {
        return;
    }

    const { images, currentIndex } = window.jtamFullscreenState;
    let newIndex = currentIndex + direction;

    if (newIndex < 0) {
        newIndex = images.length - 1;
    }

    if (newIndex >= images.length) {
        newIndex = 0;
    }

    jtamGoToFullscreenImage(newIndex);
}

function jtamGoToFullscreenImage(index) {
    if (!window.jtamFullscreenState) {
        return;
    }

    const { images, assetName } = window.jtamFullscreenState;

    if (index < 0 || index >= images.length) {
        return;
    }

    window.jtamFullscreenState.currentIndex = index;

    const imgElement = document.getElementById("jtamFullscreenImg");
    const counterElement = document.querySelector(".jtam-image-counter");
    const thumbnailImage = document.querySelector(".jtam-thumbnail-img");

    if (imgElement) {
        imgElement.src = images[index];
        imgElement.alt = `${assetName} - Image ${index + 1}`;
    }

    if (thumbnailImage) {
        thumbnailImage.src = images[index];
    }

    if (counterElement) {
        counterElement.textContent = `${index + 1} / ${images.length}`;
    }

    document.querySelectorAll(".jtam-thumbnail-item").forEach((thumb, thumbIndex) => {
        thumb.classList.toggle("active", thumbIndex === index);
    });

    jtamUpdateFullscreenNav();
}

function jtamUpdateFullscreenNav() {
    if (!window.jtamFullscreenState) {
        return;
    }

    const { images } = window.jtamFullscreenState;
    const prevBtn = document.querySelector(".jtam-fullscreen-prev");
    const nextBtn = document.querySelector(".jtam-fullscreen-next");

    if (!prevBtn || !nextBtn) {
        return;
    }

    const displayValue = images.length <= 1 ? "none" : "flex";
    prevBtn.style.display = displayValue;
    nextBtn.style.display = displayValue;
}

function jtamLoadFeedback(assetId) {
    const BASE_URL = window.BASE_URL || "/jasaan-tourism";

    fetch(`${BASE_URL}/backend/get_feedback.php?id=${assetId}`)
        .then((response) => response.json())
        .then((feedbacks) => {
            const feedbackList = document.getElementById("jtamFeedbackList");

            if (!feedbackList) {
                return;
            }

            if (!Array.isArray(feedbacks) || feedbacks.length === 0) {
                feedbackList.innerHTML = '<p class="jtam-empty-state">No feedback yet. Be the first to share your experience.</p>';
                return;
            }

            feedbackList.innerHTML = feedbacks.map((feedback) => `
                <div class="jtam-feedback">
                    <div class="jtam-feedback-header">
                        <div class="left">
                            <img src="${feedback.profile_picture ? `${BASE_URL}/${feedback.profile_picture}` : `${BASE_URL}/uploads/profile_pictures/default-user.jpg`}" class="jtam-profile-pic" alt="Profile picture">
                            <span class="jtam-name">${escapeHTML(feedback.full_name)}</span>
                        </div>
                        <div class="jtam-stars">
                            ${buildFeedbackStars(feedback.rating)}
                        </div>
                    </div>
                    <div class="jtam-comment">
                        ${formatMultilineText(feedback.comment, "")}
                    </div>
                    <hr style="border: #06b5d439 solid 0.1px; margin: 10px 0;">
                </div>
            `).join("");
        })
        .catch((err) => {
            console.error("Failed to load feedback:", err);
        });
}

function jtamInit(assetId) {
    const modal = document.querySelector(".jtam-overlay");

    if (!modal) {
        return;
    }

    jtamLoadFeedback(assetId);

    const slides = modal.querySelectorAll(".jtam-slide");
    const nextBtn = modal.querySelector(".jtam-next");
    const prevBtn = modal.querySelector(".jtam-prev");
    const carousel = modal.querySelector("#jtamCarousel");
    let currentIndex = 0;

    function showSlide(index) {
        slides.forEach((slide) => slide.classList.remove("jtam-active"));
        slides[index].classList.add("jtam-active");
    }

    if (slides.length > 1) {
        nextBtn.onclick = () => {
            currentIndex = (currentIndex + 1) % slides.length;
            showSlide(currentIndex);
        };

        prevBtn.onclick = () => {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            showSlide(currentIndex);
        };
    } else {
        nextBtn.style.display = "none";
        prevBtn.style.display = "none";
    }

    if (carousel) {
        let startX = 0;

        carousel.addEventListener("touchstart", (event) => {
            startX = event.touches[0].clientX;
        });

        carousel.addEventListener("touchend", (event) => {
            const endX = event.changedTouches[0].clientX;

            if (startX - endX > 50 && slides.length > 1) {
                nextBtn.click();
            }

            if (endX - startX > 50 && slides.length > 1) {
                prevBtn.click();
            }
        });
    }

    let rating = 0;
    const ratingStars = modal.querySelectorAll('.jtam-feedback-form .jtam-stars span');
    ratingStars.forEach((star, index) => {
        star.onclick = () => {
            rating = index + 1;

            ratingStars.forEach((item, itemIndex) => {
                item.classList.toggle("active", itemIndex < rating);
            });
        };
    });

    window.jtamGetRating = () => rating;

    const mapDiv = modal.querySelector("#jtamMap");

    if (!mapDiv) {
        return;
    }

    const lat = parseFloat(mapDiv.dataset.lat);
    const lng = parseFloat(mapDiv.dataset.lng);

    if (isNaN(lat) || isNaN(lng)) {
        mapDiv.innerHTML = '<div class="jtam-map-empty">Map coordinates are not available yet.</div>';
        return;
    }

    if (mapDiv._leaflet_id) {
        mapDiv._leaflet_id = null;
        mapDiv.innerHTML = "";
    }

    const viewMap = L.map(mapDiv, {
        zoomControl: true,
        zoomAnimation: true,
        fadeAnimation: true
    }).setView([lat, lng], 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap"
    }).addTo(viewMap);

    L.marker([lat, lng]).addTo(viewMap).bindPopup(
        `<div style="text-align:center;">
            <strong>Location</strong><br><br>
            <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="display:inline-block;padding:8px 12px;background:#06B6D4;color:#fff;border-radius:999px;text-decoration:none;font-size:13px;">
                Open in Google Maps
            </a>
        </div>`
    );

    requestAnimationFrame(() => {
        viewMap.invalidateSize();
    });
}

function jtamSubmit(assetId, triggerButton) {
    const text = document.getElementById("jtamText")?.value || "";
    const rating = typeof window.jtamGetRating === "function" ? window.jtamGetRating() : 0;
    const submitBtn = triggerButton || document.querySelector(".jtam-feedback-form .jtam-submit-btn");

    if (window.ActionLock?.isBusy(submitBtn)) {
        return;
    }

    window.ActionLock?.setBusy(submitBtn, true, { busyText: "Submitting..." });

    fetch("/jasaan-tourism/backend/submit_feedback.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `asset_id=${assetId}&rating=${rating}&comment=${encodeURIComponent(text)}`
    })
        .then((response) => response.text())
        .then((result) => {
            const success = result === "success";
            showModalToast(success ? "Feedback submitted!" : "Login required", success ? "success" : "error");

            if (!success) {
                window.ActionLock?.setBusy(submitBtn, false, { idleText: "Submit" });
                return;
            }

            jtamLoadFeedback(assetId);

            const avgElem = document.getElementById("jtamAvg");
            if (avgElem) {
                avgElem.innerText = Number(avgElem.innerText || 0).toFixed(1);
            }

            const textArea = document.getElementById("jtamText");
            if (textArea) {
                textArea.value = "";
            }

            document.querySelectorAll('.jtam-feedback-form .jtam-stars span').forEach((star) => {
                star.classList.remove("active");
            });

            window.jtamGetRating = () => 0;
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "Submit" });
        })
        .catch((error) => {
            console.error("Feedback submit failed:", error);
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "Submit" });
            showModalToast("Unable to submit feedback right now.", "error");
        });
}
