let editSelectedImages = [];
let editExistingImages = [];
let editRemovedImages = [];
let isSubmitting = false;

const editPreview = document.getElementById("edit_imagesPreview");
const editCount = document.getElementById("edit_imageCount");
const editThumbnailInput = document.getElementById("edit_thumbnailInput");
const editThumbnailPreview = document.getElementById("edit_thumbnailPreview");
const editDropZone = document.getElementById("edit_dropZone");
const editImagesInput = document.getElementById("edit_imagesInput");
const editThumbBox = editThumbnailPreview.parentElement;
const EDIT_MAX_IMAGES = 100;

function updateEditCount() {
    editCount.innerText = `${editExistingImages.length + editSelectedImages.length} / ${EDIT_MAX_IMAGES}`;
}

function syncEditUploadStates() {
    const hasThumbnail = editThumbnailPreview.children.length > 0;
    const hasGalleryImages = editExistingImages.length + editSelectedImages.length > 0;

    editThumbBox.classList.toggle("has-image", hasThumbnail);
    editDropZone.classList.toggle("has-image", hasGalleryImages);
}

function renderEditImages() {
    editPreview.innerHTML = "";

    editExistingImages.forEach((img) => {
        const wrapper = document.createElement("div");
        wrapper.classList.add("image-item", "image-item--existing");

        const previewImage = document.createElement("img");
        previewImage.src = `${window.BASE_URL}/uploads/${img}`;
        previewImage.alt = "Existing image";

        const removeBtn = createEditRemoveButton(() => {
            editExistingImages = editExistingImages.filter((image) => image !== img);

            if (!editRemovedImages.includes(img)) {
                editRemovedImages.push(img);
            }

            renderEditImages();
        });

        wrapper.appendChild(previewImage);
        wrapper.appendChild(removeBtn);
        editPreview.appendChild(wrapper);
    });

    editSelectedImages.forEach((file, index) => {
        const wrapper = document.createElement("div");
        wrapper.classList.add("image-item", "new-image");

        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.alt = file.name;

        const removeBtn = createEditRemoveButton(() => {
            editSelectedImages.splice(index, 1);
            renderEditImages();
        });

        wrapper.appendChild(img);
        wrapper.appendChild(removeBtn);
        editPreview.appendChild(wrapper);
    });

    updateEditCount();
    syncEditUploadStates();
}

function addEditFiles(files) {
    Array.from(files).forEach((file) => {
        if (editExistingImages.length + editSelectedImages.length < EDIT_MAX_IMAGES) {
            editSelectedImages.push(file);
        }
    });

    renderEditImages();
}

function createEditRemoveButton(onRemove) {
    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.classList.add("remove-btn");
    removeBtn.innerHTML = "&times;";
    removeBtn.setAttribute("aria-label", "Remove image");
    removeBtn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        onRemove();
    });
    return removeBtn;
}

function resetSubmitState(submitBtn) {
    isSubmitting = false;

    if (!submitBtn) {
        return;
    }

    window.ActionLock?.setBusy(submitBtn, false, { idleText: "UPDATE ITEM" });
}

function fillEditTravelFields(data) {
    document.getElementById("edit_transportation").value = data.transportation || "";
    document.getElementById("edit_nearby_stay").value = data.nearby_stay || "";
    document.getElementById("edit_travel_tips").value = data.travel_tips || "";
    document.getElementById("edit_estimated_cost").value = data.estimated_cost || "";
    document.getElementById("edit_travel_time").value = data.travel_time || "";
    document.getElementById("edit_best_time").value = data.best_time || "";
    document.getElementById("edit_difficulty").value = data.difficulty || "";
}

function setEditTypeSelections(typeIds) {
    const selectedIds = String(typeIds || "")
        .split(",")
        .map((id) => id.trim())
        .filter(Boolean);

    document
        .querySelectorAll('#editTypePicker input[name="type_ids[]"]')
        .forEach((input) => {
            input.checked = selectedIds.includes(input.value);
        });
}

function editAsset(id) {
    const baseUrl = window.BASE_URL || "/jasaan-tourism";

    editSelectedImages = [];
    editExistingImages = [];
    editRemovedImages = [];
    editPreview.innerHTML = "";
    updateEditCount();

    document.getElementById("editAssetModal").style.display = "flex";

    fetch(`${baseUrl}/backend/get_asset.php?id=${id}`)
        .then((res) => res.json())
        .then((data) => {
            document.getElementById("edit_asset_id").value = data.asset_id || "";
            document.getElementById("edit_asset_name").value = data.asset_name || "";
            setEditTypeSelections(data.type_ids || data.type_id || "");
            document.getElementById("edit_asset_status").value = data.asset_status || "open";
            document.getElementById("edit_status_note").value = data.status_note || "";
            document.getElementById("edit_location").value = data.location || "";
            document.getElementById("edit_description").value = data.description || "";
            document.getElementById("edit_phone").value = data.phone_number || "";
            document.getElementById("edit_email").value = data.email || "";

            document.getElementById("edit_facebook").value = data.facebook || "";
            document.getElementById("edit_instagram").value = data.instagram || "";
            document.getElementById("edit_twitter").value = data.twitter || "";
            document.getElementById("edit_tiktok").value = data.tiktok || "";

            document.getElementById("edit_lat").value = data.latitude || "";
            document.getElementById("edit_lng").value = data.longitude || "";

            fillEditTravelFields(data);
            window.syncLocationFieldState?.("edit");

            editThumbnailPreview.innerHTML = data.thumbnail
                ? `<img src="${window.BASE_URL}/uploads/${data.thumbnail}" alt="Thumbnail">`
                : "";

            editExistingImages = Array.isArray(data.images) ? data.images : [];
            renderEditImages();
            syncEditUploadStates();

            setTimeout(() => {
                if (typeof initEditMap === "function") {
                    initEditMap(data.latitude, data.longitude);

                    setTimeout(() => {
                        if (typeof editMap !== "undefined" && editMap) {
                            editMap.invalidateSize();
                        }
                    }, 200);
                }
            }, 250);
        })
        .catch((err) => {
            console.error(err);
            window.showToast?.("Failed to load asset details.", "error");
        });
}

function closeEditModal() {
    document.getElementById("editAssetModal").style.display = "none";
}

function confirmDelete(id) {
    window.openConfirmModal?.(
        "Delete Asset",
        "Move this asset to the Recycle Bin? You can restore it later.",
        () => deleteAsset(id)
    );
}

function deleteAsset(id) {
    const baseUrl = window.BASE_URL || "/jasaan-tourism";

    return fetch(`${baseUrl}/backend/delete_asset.php?id=${id}`)
        .then((res) => res.json())
        .then((data) => {
            if (data.status === "success") {
                window.showToast?.(data.message, "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                window.showToast?.(data.message, "error");
            }
        })
        .catch((err) => {
            console.error(err);
            window.showToast?.("Delete failed!", "error");
        });
}

document.addEventListener("DOMContentLoaded", function () {
    editThumbnailInput.addEventListener("change", function () {
        const file = this.files[0];
        editThumbnailPreview.innerHTML = "";

        if (!file) {
            return;
        }

        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        editThumbnailPreview.appendChild(img);
        syncEditUploadStates();
    });

    editImagesInput.addEventListener("change", function () {
        addEditFiles(this.files);
        this.value = "";
    });

    editDropZone.addEventListener("dragover", (e) => {
        e.preventDefault();
        editDropZone.classList.add("dragover");
    });

    editDropZone.addEventListener("dragleave", () => {
        editDropZone.classList.remove("dragover");
    });

    editDropZone.addEventListener("drop", (e) => {
        e.preventDefault();
        editDropZone.classList.remove("dragover");
        addEditFiles(e.dataTransfer.files);
    });

    document.getElementById("editAssetForm").addEventListener("submit", function (e) {
        e.preventDefault();

        if (isSubmitting) {
            return;
        }

        window.openConfirmModal?.(
            "Update Asset",
            "Are you sure you want to update this asset?",
            () => {
                if (isSubmitting) {
                    return;
                }

                isSubmitting = true;

                const form = document.getElementById("editAssetForm");
                const submitBtn = form.querySelector(".btn-primary");
                window.ActionLock?.setBusy(submitBtn, true, { busyText: "Saving..." });

                const formData = new FormData(form);
                const baseUrl = window.BASE_URL || "/jasaan-tourism";
                if (!formData.getAll("type_ids[]").length) {
                    window.showToast?.("Please select at least one classification.", "error");
                    resetSubmitState(submitBtn);
                    return;
                }

                editSelectedImages.forEach((file) => formData.append("images[]", file));
                editRemovedImages.forEach((image) => formData.append("removed_images[]", image));

                return fetch(`${baseUrl}/backend/update_asset.php`, {
                    method: "POST",
                    body: formData
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status === "success") {
                            window.showToast?.(data.message, "success");
                            resetSubmitState(submitBtn);
                            closeEditModal();
                            setTimeout(() => location.reload(), 1000);
                            return;
                        }

                        window.showToast?.(data.message, "error");
                        resetSubmitState(submitBtn);
                    })
                    .catch((err) => {
                        console.error(err);
                        window.showToast?.("Update failed!", "error");
                        resetSubmitState(submitBtn);
                    });
            }
        );
    });
});
