var checkedRow = []
const initRecycleBinFunction = () => {
    document.querySelectorAll('.document-table').forEach((row) => {
        const uuid = row.getAttribute('data-document-uuid')
        const restoreButton = row.querySelector(`#restore-single-${uuid}`)
        const deleteButton = row.querySelector(`#forcedelete-single-${uuid}`)

        if (restoreButton) {
            restoreButton.addEventListener('click', (e) => {
                const singleSelection = [uuid]
                restoreData(singleSelection)
            })
        }
        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                const singleSelection = [uuid]
                forceDelete(singleSelection)
            })
        }
    })
    //     row.addEventListener('click', (e) => {
    //         const documentUuid = row.getAttribute('data-document-uuid')
    //         const checkbox = row.querySelector(`#check-${documentUuid}`)
    //         checkbox.checked = !checkbox.checked
    //         handleSelection(documentUuid, checkbox.checked)
    //         document.querySelectorAll('.button-checked').forEach((btn) => {
    //             if (checkedRow.length > 0)
    //                 btn.classList.remove('hidden')
    //             else btn.classList.add('hidden')
    //         })
    //     }

    document.querySelectorAll('input[type="checkbox"]').forEach((e) => {
        e.addEventListener('click', () => {
            let uuid = e.getAttribute('data-document-uuid')
            if (e.checked) checkedRow.push(uuid)
            else checkedRow = checkedRow.filter(x => x != uuid)
            document.querySelectorAll('.button-checked').forEach((btn) => {
                if (checkedRow.length > 0) btn.classList.remove('hidden')
                else btn.classList.add('hidden')
            })
        })
    })
    document.getElementById('restore-batch').addEventListener('click', () => {
        checkedRow = addSelectedBox()
        restoreData(checkedRow)
        checkedRow = []
        removeChecked()
        hiddenAgenda()
    })
    document.getElementById('force-delete-batch').addEventListener('click', () => {
        checkedRow = addSelectedBox()
        forceDelete(checkedRow)
        checkedRow = []
        removeChecked()
        hiddenAgenda()
    })
}
const addSelectedBox = () => {
    let selected = []
    document.querySelectorAll('input[type="checkbox"]').forEach((e) => {
        if (e.checked) {
            const uuid = e.getAttribute('data-document-uuid')
            selected.push(uuid)
        }
    })


    return selected
}
const handleSelection = (id, checked) => {
    if (checked)
        checkedRow.push(id)
    else checkedRow = checkedRow.filter(x => x != id)
}
const removeChecked = () => {
    document.querySelectorAll('input[type="checkbox"]').forEach((e) => {
        e.checked = false
    })
}
const hiddenAgenda = () => {
    document.querySelectorAll('.button-checked').forEach((btn) => {
        btn.classList.add('hidden')
    })
}
const restoreData = async (uuids) => {
    try {
        const response = await fetch('/admin/recycle-bin/restore', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                id: uuids
            })
        })
        const data = await response.json()
        displayAlert(data.type, data.message)
        if (data.type == 'success') {
            // window.location.reload()
            document.querySelector('#container-list-recycle-bin').innerHTML = data.html
        }
    } catch (error) {
        console.error('Error when fetching...')
    }
}
const forceDelete = async (uuids) => {
    const isConfirmed = confirm('Apakah yakin melakukan force delete? Data akan hilang selamanya!');
    if (!isConfirmed) {
        return
    }
    try {
        const response = await fetch('/admin/recycle-bin/force-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                id: uuids
            })
        })
        const data = await response.json()
        displayAlert(data.type, data.message)
        if (data.type == 'success')
            // window.location.reload()
            document.querySelector('#container-list-recycle-bin').innerHTML = data.html
    } catch (error) {
        console.error('Error when fetching...')
    }
}

const displayAlert = (type, message) => {
    const container = document.getElementById('alert-container');

    // Tentukan kelas berdasarkan tipe (sukses/error)
    let bgColor, borderColor, iconClass, textColor;

    if (type === 'success') {
        bgColor = 'bg-green-50';
        borderColor = 'border-green-200';
        iconClass = 'fas fa-check-circle text-green-400';
        textColor = 'text-green-800';
    } else if (type === 'error') {
        bgColor = 'bg-red-50';
        borderColor = 'border-red-200';
        iconClass = 'fas fa-exclamation-circle text-red-400';
        textColor = 'text-red-800';
    } else {
        return; // Tipe tidak dikenal
    }

    // Buat HTML alert baru
    const alertHTML = `
        <div class="mb-6 ${bgColor} border ${borderColor} rounded-lg p-4">
            <div class="flex">
                <i class="${iconClass} mr-3 mt-1" aria-hidden="true"></i>
                <div class="text-sm ${textColor} text-hover">${message}</div>
            </div>
        </div>
    `;

    // Kosongkan container dan masukkan alert baru
    container.innerHTML = alertHTML;

    // Optional: Hilangkan alert setelah beberapa detik (misalnya 5 detik)
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}
export { initRecycleBinFunction }