function deleteAsset(id) {
    if (!confirm("Move this asset to the Recycle Bin?")) return;

    fetch("/jasaan-tourism/backend/delete_asset.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
    .then(res => res.text())
    .then(() => location.reload());
}
