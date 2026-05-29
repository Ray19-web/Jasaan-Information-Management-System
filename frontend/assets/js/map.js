const DEFAULT_CENTER = [8.6549, 124.7550];
const DEFAULT_ZOOM = 12;
const LOCATION_SEARCH_LIMIT = 4;
const GOOGLE_MAPS_IFRAME_SRC_PATTERN = /<iframe\b[^>]*\bsrc=(["'])(.*?)\1/i;
const GOOGLE_MAPS_URL_TOKEN_PATTERN = /(https?:\/\/[^\s"'<>]+|(?:(?:maps\.app\.goo\.gl|goo\.gl\/maps|(?:www\.)?(?:maps\.)?google\.[^\s/"'<>]+)(?:\/[^\s"'<>]*)?))/i;
const GOOGLE_MAPS_AT_COORDINATES_PATTERN = /@(-?\d{1,2}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)/i;
const GOOGLE_MAPS_EMBED_COORDINATES_PATTERN = /!3d(-?\d{1,2}(?:\.\d+)?).*?!4d(-?\d{1,3}(?:\.\d+)?)/i;
const GOOGLE_MAPS_EMBED_REVERSED_COORDINATES_PATTERN = /!2d(-?\d{1,3}(?:\.\d+)?).*?!3d(-?\d{1,2}(?:\.\d+)?)/i;
const GOOGLE_MAPS_QUERY_COORDINATES_PATTERN = /(?:[?&](?:q|query|ll|sll|center)=)(-?\d{1,2}(?:\.\d+)?),\s*(-?\d{1,3}(?:\.\d+)?)/i;
const GOOGLE_MAPS_EMBED_LABEL_PATTERN = /!2s([^!]+)/i;
const GOOGLE_MAPS_COORDINATE_PATTERNS = [
    { pattern: GOOGLE_MAPS_AT_COORDINATES_PATTERN, latIndex: 1, lngIndex: 2 },
    { pattern: GOOGLE_MAPS_EMBED_COORDINATES_PATTERN, latIndex: 1, lngIndex: 2 },
    { pattern: GOOGLE_MAPS_EMBED_REVERSED_COORDINATES_PATTERN, latIndex: 2, lngIndex: 1 },
    { pattern: GOOGLE_MAPS_QUERY_COORDINATES_PATTERN, latIndex: 1, lngIndex: 2 }
];

let map = null;
let marker = null;
let markersLayer = L.layerGroup();
let activeAutocompleteId = 0;

const autocompleteRegistry = new Map();

function getBaseUrl() {
    return window.BASE_URL || "/jasaan-tourism";
}

function getInputId(mode) {
    return mode === "edit" ? "edit_location" : "locationInput";
}

function getLatInputId(mode) {
    return mode === "edit" ? "edit_lat" : "lat";
}

function getLngInputId(mode) {
    return mode === "edit" ? "edit_lng" : "lng";
}

function getLatDisplayId(mode) {
    return mode === "edit" ? "edit_latDisplay" : "latDisplay";
}

function getLngDisplayId(mode) {
    return mode === "edit" ? "edit_lngDisplay" : "lngDisplay";
}

function getResultsId(mode) {
    return mode === "edit" ? "edit_locationResults" : "locationResults";
}

function decodeHtmlEntities(value) {
    if (typeof value !== "string" || !value.includes("&")) {
        return value;
    }

    const textarea = document.createElement("textarea");
    textarea.innerHTML = value;
    return textarea.value;
}

function safeDecodeURIComponent(value) {
    if (typeof value !== "string") {
        return "";
    }

    try {
        return decodeURIComponent(value);
    } catch (error) {
        return value;
    }
}

function normalizeExternalUrl(value) {
    const cleanedValue = decodeHtmlEntities(String(value || ""))
        .trim()
        .replace(/^['"`]+|['"`]+$/g, "");

    if (!cleanedValue) {
        return "";
    }

    if (/^https?:\/\//i.test(cleanedValue)) {
        return cleanedValue;
    }

    if (/^(?:maps\.app\.goo\.gl|goo\.gl\/maps|(?:www\.)?(?:maps\.)?google\.)/i.test(cleanedValue)) {
        return `https://${cleanedValue}`;
    }

    return cleanedValue;
}

function extractUrlCandidate(rawInput) {
    const inputValue = String(rawInput || "").trim();

    if (!inputValue) {
        return { type: "empty", value: "" };
    }

    if (/<iframe\b/i.test(inputValue)) {
        try {
            const documentFragment = new DOMParser().parseFromString(inputValue, "text/html");
            const iframe = documentFragment.querySelector("iframe[src]");

            if (iframe?.getAttribute("src")) {
                return { type: "iframe", value: iframe.getAttribute("src") };
            }
        } catch (error) {
            
        }

        const iframeMatch = inputValue.match(GOOGLE_MAPS_IFRAME_SRC_PATTERN);

        if (iframeMatch?.[2]) {
            return { type: "iframe", value: iframeMatch[2] };
        }
    }

    const urlMatch = inputValue.match(GOOGLE_MAPS_URL_TOKEN_PATTERN);

    if (urlMatch?.[1]) {
        return { type: "url", value: urlMatch[1] };
    }

    return { type: "text", value: inputValue };
}

function isGoogleMapsShortHost(hostname = "") {
    return /(^|\.)maps\.app\.goo\.gl$/i.test(hostname) || /^goo\.gl$/i.test(hostname);
}

function isGoogleMapsHost(hostname = "") {
    return /(^|\.)(maps\.)?google\.[a-z.]+$/i.test(hostname);
}

function isCoordinatePair(lat, lng) {
    return Number.isFinite(lat)
        && Number.isFinite(lng)
        && Math.abs(lat) <= 90
        && Math.abs(lng) <= 180;
}

function createCoordinatePair(latValue, lngValue) {
    const lat = Number.parseFloat(latValue);
    const lng = Number.parseFloat(lngValue);
    return isCoordinatePair(lat, lng) ? { lat, lng } : null;
}

function looksLikeCoordinateLabel(value = "") {
    return /^-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?$/i.test(value.trim());
}

function extractCoordinatesFromGoogleMapsText(value) {
    const decodedValue = safeDecodeURIComponent(String(value || ""));
    const candidates = [String(value || ""), decodedValue];

    for (const candidate of candidates) {
        for (const extractor of GOOGLE_MAPS_COORDINATE_PATTERNS) {
            const match = candidate.match(extractor.pattern);

            if (!match) {
                continue;
            }

            const coordinates = createCoordinatePair(
                match[extractor.latIndex],
                match[extractor.lngIndex]
            );

            if (coordinates) {
                return coordinates;
            }
        }
    }

    return null;
}

function extractCoordinatesFromGoogleMapsUrl(urlObject) {
    if (!(urlObject instanceof URL)) {
        return null;
    }

    for (const key of ["q", "query", "ll", "sll", "center"]) {
        const value = urlObject.searchParams.get(key);

        if (!value) {
            continue;
        }

        const directPair = value.split(",").map((part) => part.trim());

        if (directPair.length >= 2) {
            const directCoordinates = createCoordinatePair(directPair[0], directPair[1]);

            if (directCoordinates) {
                return directCoordinates;
            }
        }
    }

    return extractCoordinatesFromGoogleMapsText(urlObject.toString());
}

function normalizeGoogleMapsLabel(value = "") {
    return safeDecodeURIComponent(String(value || ""))
        .replace(/\+/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

function extractGoogleMapsLabel(urlObject) {
    if (!(urlObject instanceof URL)) {
        return "";
    }

    const placeMatch = safeDecodeURIComponent(urlObject.pathname).match(/\/place\/([^/]+)/i);

    if (placeMatch?.[1]) {
        return normalizeGoogleMapsLabel(placeMatch[1]);
    }

    for (const key of ["q", "query"]) {
        const value = urlObject.searchParams.get(key);

        if (!value || looksLikeCoordinateLabel(value) || /^loc:/i.test(value)) {
            continue;
        }

        return normalizeGoogleMapsLabel(value);
    }

    const embedLabelMatch = safeDecodeURIComponent(urlObject.toString()).match(GOOGLE_MAPS_EMBED_LABEL_PATTERN);

    if (embedLabelMatch?.[1] && !looksLikeCoordinateLabel(embedLabelMatch[1])) {
        return normalizeGoogleMapsLabel(embedLabelMatch[1]);
    }

    return "";
}

function looksLikeGoogleMapsInput(rawInput = "") {
    const inputValue = String(rawInput || "").trim();

    if (!inputValue) {
        return false;
    }

    if (/<iframe\b/i.test(inputValue)) {
        return true;
    }

    if (/maps\.app\.goo\.gl/i.test(inputValue) || /goo\.gl\/maps/i.test(inputValue)) {
        return true;
    }

    return /(?:https?:\/\/)?(?:www\.)?(?:maps\.)?google\.[^\s/]+/i.test(inputValue)
        && (/\/maps\b/i.test(inputValue) || /@-?\d/i.test(inputValue) || /!3d-?\d/i.test(inputValue));
}

async function resolveShortGoogleMapsUrl(url) {
    const params = new URLSearchParams({ url });
    const response = await fetch(`${getBaseUrl()}/backend/resolve_google_maps.php?${params.toString()}`);
    const data = await response.json();

    if (!response.ok || !data.success || !data.final_url) {
        throw new Error(data.message || "Unable to resolve this short Google Maps link.");
    }

    return data.final_url;
}

async function parseGoogleMapsInput(rawInput) {
    const extractedCandidate = extractUrlCandidate(rawInput);

    if (!extractedCandidate.value) {
        throw new Error("Paste a Google Maps link or iframe embed code.");
    }

    const normalizedValue = normalizeExternalUrl(extractedCandidate.value);
    let parsedUrl = null;

    try {
        parsedUrl = new URL(normalizedValue);
    } catch (error) {
        throw new Error("That Google Maps input is incomplete or invalid.");
    }

    if (isGoogleMapsShortHost(parsedUrl.hostname)) {
        const resolvedUrl = await resolveShortGoogleMapsUrl(parsedUrl.toString());
        parsedUrl = new URL(resolvedUrl);
    }

    if (!isGoogleMapsHost(parsedUrl.hostname)) {
        throw new Error("Only Google Maps links and embed codes are supported here.");
    }

    const coordinates = extractCoordinatesFromGoogleMapsUrl(parsedUrl);

    if (!coordinates) {
        throw new Error("Coordinates were not found in that Google Maps link. Use a full map link, short share link, or iframe embed code.");
    }

    const label = extractGoogleMapsLabel(parsedUrl);

    return {
        ...coordinates,
        label: label || "Pinned location",
        shouldReverseLookup: !label,
        sourceType: extractedCandidate.type,
        resolvedUrl: parsedUrl.toString()
    };
}

function formatCoordinate(value) {
    const parsedValue = Number.parseFloat(value);
    return Number.isFinite(parsedValue) ? parsedValue.toFixed(6) : "Not set";
}

function updateCoordinateDisplay(mode, lat, lng) {
    const latDisplay = document.getElementById(getLatDisplayId(mode));
    const lngDisplay = document.getElementById(getLngDisplayId(mode));

    if (latDisplay) {
        latDisplay.textContent = formatCoordinate(lat);
    }

    if (lngDisplay) {
        lngDisplay.textContent = formatCoordinate(lng);
    }
}

function setCoordinateInputs(mode, lat, lng) {
    const latInput = document.getElementById(getLatInputId(mode));
    const lngInput = document.getElementById(getLngInputId(mode));

    if (latInput) {
        latInput.value = Number.isFinite(Number.parseFloat(lat)) ? Number.parseFloat(lat) : "";
    }

    if (lngInput) {
        lngInput.value = Number.isFinite(Number.parseFloat(lng)) ? Number.parseFloat(lng) : "";
    }

    updateCoordinateDisplay(mode, lat, lng);
    updateClearButtonVisibility(mode);
}

function setLocationInputValue(mode, value) {
    const input = document.getElementById(getInputId(mode));

    if (!input) {
        return;
    }

    input.value = value || "";
    updateClearButtonVisibility(mode);
}

function updateClearButtonVisibility(mode) {
    const input = document.getElementById(getInputId(mode));
    const shell = input ? input.closest(".location-search-shell") : null;
    const clearButton = shell ? shell.querySelector(".location-clear-btn") : null;

    if (!input || !clearButton) {
        return;
    }

    const latValue = document.getElementById(getLatInputId(mode))?.value || "";
    const lngValue = document.getElementById(getLngInputId(mode))?.value || "";
    clearButton.hidden = !(input.value.trim() || latValue || lngValue);
}

function setSearchStatus(mode, message) {
    const panel = document.getElementById(getResultsId(mode));

    if (!panel) {
        return;
    }

    panel.innerHTML = `<div class="location-search-status">${message}</div>`;
    panel.classList.add("is-open");
}

function closeSearchPanel(mode) {
    const panel = document.getElementById(getResultsId(mode));

    if (!panel) {
        return;
    }

    panel.innerHTML = "";
    panel.classList.remove("is-open");
}

function animateMarker(markerInstance) {
    if (!markerInstance || !markerInstance._icon) {
        return;
    }

    markerInstance._icon.classList.add("bounce");

    window.setTimeout(() => {
        markerInstance._icon?.classList.remove("bounce");
    }, 550);
}

async function reverseGeocodeLocation(lat, lng) {
    const params = new URLSearchParams({
        reverse: "1",
        lat: String(lat),
        lng: String(lng)
    });

    const response = await fetch(`${getBaseUrl()}/backend/location_search.php?${params.toString()}`);
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to reverse geocode this location.");
    }

    return data.location || "";
}

async function updateLocationFromCoordinates(mode, lat, lng, fallbackLabel = "Pinned location") {
    try {
        const resolvedLocation = await reverseGeocodeLocation(lat, lng);
        setLocationInputValue(mode, resolvedLocation || fallbackLabel);

        if (mode === "edit" && window.editMarker) {
            window.editMarker.bindPopup(`
                <b>${resolvedLocation || fallbackLabel}</b><br>
                ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}
            `);
        }

        if (mode === "add" && marker) {
            marker.bindPopup(`
                <b>${resolvedLocation || fallbackLabel}</b><br>
                ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}
            `);
        }
    } catch (error) {
        setLocationInputValue(mode, fallbackLabel);
    }
}

function placeMarker(lat, lng, label = "Selected Location", options = {}) {
    const parsedLat = Number.parseFloat(lat);
    const parsedLng = Number.parseFloat(lng);
    const shouldFly = options.fly !== false;

    if (!map || !Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
        return;
    }

    if (marker) {
        map.removeLayer(marker);
    }

    marker = L.marker([parsedLat, parsedLng], {
        draggable: true,
        riseOnHover: true
    }).addTo(map);

    marker.bindPopup(`
        <b>${label}</b><br>
        ${parsedLat.toFixed(6)}, ${parsedLng.toFixed(6)}
    `);

    if (shouldFly) {
        map.flyTo([parsedLat, parsedLng], 15, { duration: 0.9 });
        marker.openPopup();
    }

    setCoordinateInputs("add", parsedLat, parsedLng);
    animateMarker(marker);

    marker.on("dragend", async function () {
        const position = marker.getLatLng();
        placeMarker(position.lat, position.lng, "Pinned location", { fly: false });
        await updateLocationFromCoordinates("add", position.lat, position.lng, "Pinned location");
        marker?.openPopup();
    });
}

function placeEditMarker(lat, lng, label = "Selected Location", options = {}) {
    const parsedLat = Number.parseFloat(lat);
    const parsedLng = Number.parseFloat(lng);
    const shouldFly = options.fly !== false;

    if (!window.editMap || !Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
        return;
    }

    if (window.editMarker) {
        window.editMap.removeLayer(window.editMarker);
    }

    window.editMarker = L.marker([parsedLat, parsedLng], {
        draggable: true,
        riseOnHover: true
    }).addTo(window.editMap);

    window.editMarker.bindPopup(`
        <b>${label}</b><br>
        ${parsedLat.toFixed(6)}, ${parsedLng.toFixed(6)}
    `);

    if (shouldFly) {
        window.editMap.flyTo([parsedLat, parsedLng], 15, { duration: 0.9 });
        window.editMarker.openPopup();
    }

    setCoordinateInputs("edit", parsedLat, parsedLng);
    animateMarker(window.editMarker);

    window.editMarker.on("dragend", async function () {
        const position = window.editMarker.getLatLng();
        placeEditMarker(position.lat, position.lng, "Pinned location", { fly: false });
        await updateLocationFromCoordinates("edit", position.lat, position.lng, "Pinned location");
        window.editMarker?.openPopup();
    });
}

async function applyLocationSelection(mode, lat, lng, label, options = {}) {
    const parsedLat = Number.parseFloat(lat);
    const parsedLng = Number.parseFloat(lng);
    const resolvedLabel = label || "Selected Location";
    const shouldSyncInput = options.syncInput !== false;
    const shouldReverseLookup = options.reverseLookup === true;

    if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
        return;
    }

    if (mode === "edit") {
        if (!window.editMap) {
            initEditMap(parsedLat, parsedLng);
        }

        placeEditMarker(parsedLat, parsedLng, resolvedLabel, options);
    } else {
        placeMarker(parsedLat, parsedLng, resolvedLabel, options);
    }

    if (shouldSyncInput) {
        setLocationInputValue(mode, resolvedLabel);
    }

    if (shouldReverseLookup) {
        await updateLocationFromCoordinates(mode, parsedLat, parsedLng, resolvedLabel);
    }
}

window.clearLocationSelection = function clearLocationSelection(mode) {
    setLocationInputValue(mode, "");
    setCoordinateInputs(mode, "", "");
    closeSearchPanel(mode);

    if (mode === "edit") {
        if (window.editMarker && window.editMap) {
            window.editMap.removeLayer(window.editMarker);
        }

        window.editMarker = null;

        if (window.editMap) {
            window.editMap.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }

        return;
    }

    if (marker && map) {
        map.removeLayer(marker);
    }

    marker = null;

    if (map) {
        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    }
};

function buildSearchResults(mode, results) {
    const panel = document.getElementById(getResultsId(mode));

    if (!panel) {
        return;
    }

    if (!Array.isArray(results) || results.length === 0) {
        setSearchStatus(mode, "No matching places found. Try a more specific landmark or address.");
        return;
    }

    panel.innerHTML = "";
    panel.classList.add("is-open");

    results.forEach((result) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "location-search-item";

        const title = document.createElement("span");
        title.className = "location-search-item__title";
        title.textContent = result.title;

        const meta = document.createElement("span");
        meta.className = "location-search-item__meta";
        meta.textContent = result.meta || result.label;

        button.appendChild(title);
        button.appendChild(meta);

        button.addEventListener("click", async () => {
            await applyLocationSelection(
                mode,
                result.latitude,
                result.longitude,
                result.label,
                { syncInput: true, fly: true }
            );
            closeSearchPanel(mode);
        });

        panel.appendChild(button);
    });
}

function setupAutocomplete(inputId, latId, lngId, mapType = "add") {
    if (autocompleteRegistry.has(inputId)) {
        return autocompleteRegistry.get(inputId);
    }

    const input = document.getElementById(inputId);
    const panel = document.getElementById(getResultsId(mapType));

    if (!input || !panel) {
        return null;
    }

    let debounceTimer = null;
    let abortController = null;
    let lastQuery = "";
    let activeSpecialInputId = 0;

    async function searchLocations(query) {
        const trimmedQuery = query.trim();

        if (trimmedQuery.length < 2) {
            closeSearchPanel(mapType);
            return;
        }

        const searchId = ++activeAutocompleteId;

        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        setSearchStatus(mapType, "Searching for matching places...");

        try {
            const params = new URLSearchParams({
                q: trimmedQuery,
                limit: String(LOCATION_SEARCH_LIMIT)
            });

            const response = await fetch(
                `${getBaseUrl()}/backend/location_search.php?${params.toString()}`,
                { signal: abortController.signal }
            );
            const data = await response.json();

            if (searchId !== activeAutocompleteId || trimmedQuery !== input.value.trim()) {
                return;
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to search locations right now.");
            }

            buildSearchResults(mapType, data.results || []);
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            setSearchStatus(mapType, "Location search is unavailable right now. Try clicking on the map instead.");
        }
    }

    async function handleGoogleMapsInput(rawValue) {
        const trimmedValue = rawValue.trim();

        if (!trimmedValue) {
            closeSearchPanel(mapType);
            return;
        }

        const requestId = ++activeSpecialInputId;
        setSearchStatus(mapType, "Reading Google Maps link...");

        try {
            const parsedLocation = await parseGoogleMapsInput(trimmedValue);

            if (requestId !== activeSpecialInputId || trimmedValue !== input.value.trim()) {
                return;
            }

            await applyLocationSelection(
                mapType,
                parsedLocation.lat,
                parsedLocation.lng,
                parsedLocation.label,
                {
                    syncInput: true,
                    fly: true,
                    reverseLookup: parsedLocation.shouldReverseLookup
                }
            );

            closeSearchPanel(mapType);
        } catch (error) {
            if (requestId !== activeSpecialInputId || trimmedValue !== input.value.trim()) {
                return;
            }

            setSearchStatus(
                mapType,
                error instanceof Error && error.message
                    ? error.message
                    : "Unable to read that Google Maps input."
            );
        }
    }

    input.addEventListener("input", () => {
        window.clearTimeout(debounceTimer);
        updateClearButtonVisibility(mapType);

        const query = input.value.trim();

        if (query === lastQuery) {
            return;
        }

        lastQuery = query;

        if (looksLikeGoogleMapsInput(query)) {
            debounceTimer = window.setTimeout(() => {
                handleGoogleMapsInput(query);
            }, 180);
            return;
        }

        if (query.length < 2) {
            closeSearchPanel(mapType);
            return;
        }

        debounceTimer = window.setTimeout(() => {
            searchLocations(query);
        }, 260);
    });

    input.addEventListener("paste", () => {
        window.clearTimeout(debounceTimer);

        window.setTimeout(() => {
            const query = input.value.trim();

            if (!looksLikeGoogleMapsInput(query)) {
                return;
            }

            handleGoogleMapsInput(query);
        }, 0);
    });

    input.addEventListener("focus", () => {
        const query = input.value.trim();
        if (query.length >= 2 && panel.childElementCount > 0) {
            panel.classList.add("is-open");
        }
    });

    document.addEventListener("click", (event) => {
        if (!input.closest(".location-search-shell")?.contains(event.target)) {
            closeSearchPanel(mapType);
        }
    });

    updateCoordinateDisplay(mapType, document.getElementById(latId)?.value, document.getElementById(lngId)?.value);
    updateClearButtonVisibility(mapType);

    const instance = { inputId, latId, lngId, mapType };
    autocompleteRegistry.set(inputId, instance);
    return instance;
}

function loadSavedAssets() {
    if (!map) {
        return;
    }

    markersLayer.clearLayers();

    fetch(`${getBaseUrl()}/backend/get_assets_map.php`)
        .then((res) => res.json())
        .then((data) => {
            data.forEach((asset) => {
                const lat = Number.parseFloat(asset.latitude);
                const lng = Number.parseFloat(asset.longitude);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                const savedMarker = L.marker([lat, lng], { riseOnHover: true });
                savedMarker.bindPopup(`
                    <b>${asset.name}</b><br>
                    ${asset.location}<br>
                    ${lat.toFixed(6)}, ${lng.toFixed(6)}
                `);

                markersLayer.addLayer(savedMarker);
            });
        })
        .catch((error) => {
            console.error("Unable to load saved assets:", error);
        });
}

function resetMap() {
    window.clearLocationSelection("add");
    markersLayer.clearLayers();
    loadSavedAssets();
}

function initMap() {
    const mapElement = document.getElementById("map");

    if (!mapElement) {
        return;
    }

    if (!map) {
        map = L.map("map", { zoomAnimation: true }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        markersLayer.addTo(map);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap"
        }).addTo(map);

        map.on("click", async function (event) {
            const { lat, lng } = event.latlng;
            await applyLocationSelection("add", lat, lng, "Pinned location", {
                syncInput: false,
                reverseLookup: true
            });
        });
    }

    setupAutocomplete("locationInput", "lat", "lng", "add");
    setupAutocomplete("edit_location", "edit_lat", "edit_lng", "edit");
    loadSavedAssets();
}

window.editMap = null;
window.editMarker = null;

window.initEditMap = function initEditMap(lat, lng) {
    const editMapElement = document.getElementById("editMap");

    if (!editMapElement) {
        return;
    }

    const parsedLat = Number.parseFloat(lat);
    const parsedLng = Number.parseFloat(lng);
    const center = Number.isFinite(parsedLat) && Number.isFinite(parsedLng)
        ? [parsedLat, parsedLng]
        : DEFAULT_CENTER;
    const zoom = Number.isFinite(parsedLat) && Number.isFinite(parsedLng) ? 13 : DEFAULT_ZOOM;

    if (window.editMap) {
        window.editMap.remove();
    }

    window.editMap = L.map("editMap", { zoomAnimation: true }).setView(center, zoom);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap"
    }).addTo(window.editMap);

    window.editMap.on("click", async function (event) {
        const { lat: nextLat, lng: nextLng } = event.latlng;
        await applyLocationSelection("edit", nextLat, nextLng, "Pinned location", {
            syncInput: false,
            reverseLookup: true
        });
    });

    if (Number.isFinite(parsedLat) && Number.isFinite(parsedLng)) {
        placeEditMarker(parsedLat, parsedLng, document.getElementById("edit_location")?.value || "Saved location", {
            fly: false
        });
    } else {
        updateCoordinateDisplay("edit", "", "");
    }
};

document.addEventListener("DOMContentLoaded", initMap);

window.setupAutocomplete = setupAutocomplete;
window.loadSavedAssets = loadSavedAssets;
window.resetMap = resetMap;
window.syncLocationFieldState = function syncLocationFieldState(mode) {
    updateCoordinateDisplay(
        mode,
        document.getElementById(getLatInputId(mode))?.value,
        document.getElementById(getLngInputId(mode))?.value
    );
    updateClearButtonVisibility(mode);
};
