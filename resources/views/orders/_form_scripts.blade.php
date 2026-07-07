<script>
// ─── Quill rich text editor ───────────────────────────────
const quill = new Quill('#quill-editor', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: '#quill-toolbar',
            handlers: {
                // Toolbar image button → file picker → upload → insert URL
                image: function () {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/png,image/jpeg,image/gif,image/webp');
                    input.click();
                    input.addEventListener('change', () => {
                        const file = input.files[0];
                        if (file) uploadImageFile(file);
                    });
                }
            }
        },
        clipboard: { matchVisual: false },
    },
    placeholder: 'Jersey specifications, fabric requirements, colours, sponsor logos, special instructions…',
});

// ─── Shared upload helper ─────────────────────────────────
async function uploadImageFile(file) {
    if (file.size > 5 * 1024 * 1024) {
        alert('Image must be under 5 MB.');
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const res = await fetch('{{ route("orders.editor-image") }}', {
            method: 'POST',
            body:   formData,
        });
        if (!res.ok) throw new Error('Upload failed');
        const data  = await res.json();
        const range = quill.getSelection(true);
        quill.insertEmbed(range ? range.index : quill.getLength(), 'image', data.url, Quill.sources.USER);
        quill.setSelection((range ? range.index : quill.getLength()) + 1, Quill.sources.SILENT);
    } catch (err) {
        console.error(err);
        alert('Image upload failed. Please try again.');
    }
}

// ─── Intercept paste & drag-drop ─────────────────────────
// When the user pastes or drops an image, Quill would normally embed
// a base64 blob. We intercept before that happens, upload the blob,
// and insert the returned URL instead — keeping the POST small.
quill.root.addEventListener('paste', async (e) => {
    const items = e.clipboardData && e.clipboardData.items;
    if (!items) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.stopPropagation();  // prevent Quill's default base64 paste
            e.preventDefault();
            const file = item.getAsFile();
            if (file) await uploadImageFile(file);
            return;
        }
    }
    // Non-image paste — let Quill handle normally
});

quill.root.addEventListener('drop', async (e) => {
    const files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;

    const file = files[0];
    if (file.type.startsWith('image/')) {
        e.preventDefault();
        await uploadImageFile(file);
    }
});

// ─── On submit: flush Quill HTML → hidden input ───────────
// At this point the HTML only contains <img src="https://..."> URLs,
// never base64 blobs, so the POST stays small.
document.getElementById('order-form').addEventListener('formdata', function (e) {
    e.formData.set('details', quill.root.innerHTML);
});
document.getElementById('order-form').addEventListener('submit', function () {
    document.getElementById('details-input').value = quill.root.innerHTML;
});

// ─── Dynamic player rows ──────────────────────────────────
let playerIndex = document.querySelectorAll('.player-row').length;

function renumberRows() {
    document.querySelectorAll('.player-row').forEach((row, i) => {
        const numCell = row.querySelector('.row-num');
        if (numCell) numCell.textContent = i + 1;

        row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/players\[\d+\]/, `players[${i}]`);
        });
    });

    const noRows = document.getElementById('no-players-row');
    const hasRows = document.querySelectorAll('.player-row').length > 0;
    if (noRows) noRows.style.display = hasRows ? 'none' : '';
}

function addPlayerRow() {
    const noRow = document.getElementById('no-players-row');
    if (noRow) noRow.style.display = 'none';

    const i = playerIndex++;
    const tr = document.createElement('tr');
    tr.className = 'player-row';
    tr.innerHTML = `
        <td class="ps-3 text-muted row-num">${document.querySelectorAll('.player-row').length + 1}</td>
        <input type="hidden" name="players[${i}][id]" value="">
        <td>
            <input type="text"
                   name="players[${i}][player_name]"
                   class="form-control form-control-sm"
                   placeholder="Player name"
                   required>
        </td>
        <td>
            <input type="text"
                   name="players[${i}][jersey_number]"
                   class="form-control form-control-sm text-center"
                   placeholder="10">
        </td>
        <td>
            <select name="players[${i}][size]" class="form-select form-select-sm">
                <option value="">—</option>
                ${['XS','S','M','L','XL','XXL','3XL'].map(s => `<option>${s}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="text"
                   name="players[${i}][notes]"
                   class="form-control form-control-sm"
                   placeholder="Special instructions">
        </td>
        <td>
            <button type="button"
                    class="btn btn-sm btn-outline-danger remove-player-btn"
                    title="Remove">
                <i class="bi bi-x"></i>
            </button>
        </td>
    `;

    document.getElementById('players-body').appendChild(tr);
    tr.querySelector('input[name$="[player_name]"]').focus();
}

document.getElementById('add-player-btn').addEventListener('click', addPlayerRow);

document.getElementById('players-body').addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-player-btn');
    if (btn) {
        btn.closest('tr').remove();
        renumberRows();
    }
});

renumberRows();
</script>