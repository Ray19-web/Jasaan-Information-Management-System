function openModal() {
    document.getElementById("addAssetModal").style.display = "flex";

    setTimeout(() => {
        if (map) {
            map.invalidateSize();
            resetMap();
        }
    }, 300);
}

function closeModal() {
    document.getElementById("addAssetModal").style.display = "none";
}

function createAddRemoveButton(onRemove) {
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

let selectedImages = [];
let isAddSubmitting = false;

const thumbBox = document.querySelector("#thumbnailPreview").parentElement;
const galleryBox = document.getElementById("dropZone");

function syncAddUploadStates() {
    const hasThumbnail = document.getElementById("thumbnailPreview").children.length > 0;
    const hasGalleryImages = selectedImages.length > 0;

    thumbBox.classList.toggle("has-image", hasThumbnail);
    galleryBox.classList.toggle("has-image", hasGalleryImages);
}

document.getElementById("thumbnailInput").addEventListener("change", function () {
    const preview = document.getElementById("thumbnailPreview");
    preview.innerHTML = "";

    const file = this.files[0];
    if (file) {
        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        preview.appendChild(img);
    }

    syncAddUploadStates();
});

const maxImages = 100;

const input = document.getElementById("imagesInput");
const preview = document.getElementById("imagesPreview");
const countDisplay = document.getElementById("imageCount");
const dropZone = document.getElementById("dropZone");

function updateCount() {
    countDisplay.innerText = `${selectedImages.length} / ${maxImages}`;
}

function renderImages() {
    preview.innerHTML = "";

    selectedImages.forEach((file, index) => {
        const wrapper = document.createElement("div");
        wrapper.classList.add("image-item");

        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);

        const removeBtn = createAddRemoveButton(() => {
            selectedImages.splice(index, 1);
            renderImages();
        });

        wrapper.appendChild(img);
        wrapper.appendChild(removeBtn);
        preview.appendChild(wrapper);
    });

    updateCount();
    syncAddUploadStates();
}

function addFiles(files) {
    Array.from(files).forEach(file => {
        if (selectedImages.length < maxImages) {
            selectedImages.push(file);
        }
    });

    renderImages();
}

input.addEventListener("change", function () {
    addFiles(this.files);
    this.value = "";
});

dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});

dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
});

dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");

    addFiles(e.dataTransfer.files);
});

function appendRow(data) {
    const table = document.getElementById("assetsTable");

    setTimeout(() => location.reload(), 1000);
    const row = `
        <tr>
            <td>
                <div class="asset-info">
                    <img src="/jasaan-tourism/uploads/${data.thumbnail || "default.png"}" class="asset-img">
                    <div>
                        <strong>${data.name}</strong><br>
                        <small>ID: ${data.asset_id}</small>
                    </div>
                </div>
            </td>

            <td>
                <span class="badge">${data.type_name ?? ""}</span>
            </td>

            <td>${data.location}</td>

            <td>
                <i class="fa-solid fa-pen action-icon edit"></i>
                <i class="fa-solid fa-trash action-icon delete"></i>
            </td>
        </tr>
    `;

    table.insertAdjacentHTML("afterbegin", row);
}

document.getElementById("assetForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const baseUrl = window.BASE_URL || "/jasaan-tourism";
    const submitBtn = form.querySelector(".btn-primary");

    if (isAddSubmitting || window.ActionLock?.isBusy(submitBtn)) {
        return;
    }

    isAddSubmitting = true;
    window.ActionLock?.setBusy(submitBtn, true, { busyText: "Saving..." });

    if (!formData.getAll("type_ids[]").length) {
        isAddSubmitting = false;
        window.ActionLock?.setBusy(submitBtn, false, { idleText: "ADD ITEM" });
        window.showToast?.("Please select at least one classification.", "error");
        return;
    }

    selectedImages.forEach((file) => {
        formData.append("images[]", file);
    });

    fetch(`${baseUrl}/backend/save_asset.php`, {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                window.showToast?.(data.message, "success");

                appendRow(data.data);
                resetMap();

                form.reset();
                document.getElementById("thumbnailPreview").innerHTML = "";
                document.getElementById("imagesPreview").innerHTML = "";

                selectedImages = [];
                document.getElementById("imagesPreview").innerHTML = "";
                updateCount();
                syncAddUploadStates();

                isAddSubmitting = false;
                window.ActionLock?.setBusy(submitBtn, false, { idleText: "ADD ITEM" });
                closeModal();
            } else {
                isAddSubmitting = false;
                window.ActionLock?.setBusy(submitBtn, false, { idleText: "ADD ITEM" });
                window.showToast?.(data.message, "error");
            }
        })
        .catch(err => {
            console.error(err);
            isAddSubmitting = false;
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "ADD ITEM" });
            window.showToast?.("Something went wrong!", "error");
        });
});
