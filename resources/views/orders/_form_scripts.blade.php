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
                },

                // Custom link handler → opens our modal
                link: function () {
                    const range     = quill.getSelection();
                    const [leaf]    = range ? quill.getLeaf(range.index) : [null];
                    const existing  = leaf?.parent?.domNode?.tagName === 'A'
                                      ? leaf.parent.domNode.getAttribute('href')
                                      : null;
                    openLinkModal(range, existing);
                }
            }
        },
        clipboard: { matchVisual: false },
    },
    placeholder: 'Jersey specifications, fabric requirements, colours, sponsor logos, special instructions…',
});

// ─── Link modal ───────────────────────────────────────────
// Inject modal HTML once into the page
(function injectLinkModal() {
    if (document.getElementById('ql-link-modal')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="ql-link-modal"
             style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);
                    align-items:center;justify-content:center">
            <div style="background:#fff;border-radius:.5rem;padding:1.5rem;width:100%;max-width:420px;
                        box-shadow:0 .5rem 2rem rgba(0,0,0,.2);margin:1rem">
                <h6 style="margin:0 0 1rem;font-weight:700">
                    <i class="bi bi-link-45deg me-1"></i>Insert / Edit Link
                </h6>
                <div style="margin-bottom:.75rem">
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem">
                        URL <span style="color:#dc3545">*</span>
                    </label>
                    <input id="ql-link-url"
                           type="url"
                           placeholder="https://example.com"
                           style="width:100%;padding:.45rem .6rem;border:1px solid #ced4da;
                                  border-radius:.35rem;font-size:.9rem;box-sizing:border-box">
                </div>
                <div style="margin-bottom:.75rem">
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem">
                        Link text
                    </label>
                    <input id="ql-link-text"
                           type="text"
                           placeholder="Leave blank to keep selection"
                           style="width:100%;padding:.45rem .6rem;border:1px solid #ced4da;
                                  border-radius:.35rem;font-size:.9rem;box-sizing:border-box">
                </div>
                <div style="margin-bottom:1rem">
                    <label style="font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem">
                        <input id="ql-link-newtab" type="checkbox" checked>
                        Open in new tab
                    </label>
                </div>
                <div style="display:flex;gap:.5rem;justify-content:flex-end">
                    <button id="ql-link-remove"
                            type="button"
                            style="padding:.35rem .85rem;border:1px solid #dc3545;background:#fff;
                                   color:#dc3545;border-radius:.35rem;cursor:pointer;font-size:.85rem;
                                   display:none">
                        Remove link
                    </button>
                    <button id="ql-link-cancel"
                            type="button"
                            style="padding:.35rem .85rem;border:1px solid #6c757d;background:#fff;
                                   color:#495057;border-radius:.35rem;cursor:pointer;font-size:.85rem">
                        Cancel
                    </button>
                    <button id="ql-link-save"
                            type="button"
                            style="padding:.35rem .85rem;border:none;background:#0d6efd;
                                   color:#fff;border-radius:.35rem;cursor:pointer;font-size:.85rem">
                        Save
                    </button>
                </div>
            </div>
        </div>
    `);
}());

let _linkRange = null;

function openLinkModal(range, existingHref) {
    _linkRange = range;
    const modal    = document.getElementById('ql-link-modal');
    const urlInput = document.getElementById('ql-link-url');
    const txtInput = document.getElementById('ql-link-text');
    const newTab   = document.getElementById('ql-link-newtab');
    const removeBtn= document.getElementById('ql-link-remove');

    urlInput.value = existingHref || '';
    newTab.checked = true;

    // Pre-fill text from selection
    const selText = range && range.length > 0
        ? quill.getText(range.index, range.length).trim()
        : '';
    txtInput.value = selText;

    // Show "Remove link" button only when editing an existing link
    removeBtn.style.display = existingHref ? '' : 'none';

    modal.style.display = 'flex';
    setTimeout(() => urlInput.focus(), 50);
}

function closeLinkModal() {
    document.getElementById('ql-link-modal').style.display = 'none';
    _linkRange = null;
}

document.getElementById('ql-link-cancel').addEventListener('click', closeLinkModal);

document.getElementById('ql-link-modal').addEventListener('click', function (e) {
    if (e.target === this) closeLinkModal();
});

document.getElementById('ql-link-url').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') document.getElementById('ql-link-save').click();
    if (e.key === 'Escape') closeLinkModal();
});

document.getElementById('ql-link-save').addEventListener('click', function () {
    const url     = document.getElementById('ql-link-url').value.trim();
    const text    = document.getElementById('ql-link-text').value.trim();
    const newTab  = document.getElementById('ql-link-newtab').checked;

    if (!url) {
        document.getElementById('ql-link-url').style.borderColor = '#dc3545';
        document.getElementById('ql-link-url').focus();
        return;
    }
    document.getElementById('ql-link-url').style.borderColor = '#ced4da';

    const range = _linkRange;
    closeLinkModal();

    if (text && range && range.length === 0) {
        // No selection — insert new text as the link
        quill.insertText(range ? range.index : quill.getLength(), text, 'link', url, Quill.sources.USER);
        const insertedIdx = (range ? range.index : 0) + text.length;
        quill.setSelection(insertedIdx, 0, Quill.sources.SILENT);
    } else if (range && range.length > 0) {
        // Replace selected text with link (optionally with new text)
        if (text) {
            quill.deleteText(range.index, range.length, Quill.sources.USER);
            quill.insertText(range.index, text, 'link', url, Quill.sources.USER);
        } else {
            quill.formatText(range.index, range.length, 'link', url, Quill.sources.USER);
        }
    }

    // Set target=_blank via DOM after Quill creates the element
    if (newTab) {
        setTimeout(() => {
            quill.root.querySelectorAll('a[href="' + CSS.escape(url) + '"]').forEach(a => {
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
            });
        }, 50);
    }
});

document.getElementById('ql-link-remove').addEventListener('click', function () {
    const range = _linkRange;
    closeLinkModal();
    if (range) {
        // Expand selection to cover the whole link if cursor is inside it
        const [leaf, offset] = quill.getLeaf(range.index);
        if (leaf?.parent?.domNode?.tagName === 'A') {
            const linkEl  = leaf.parent.domNode;
            const linkIdx = quill.getIndex(leaf.parent);
            const linkLen = linkEl.textContent.length;
            quill.formatText(linkIdx, linkLen, 'link', false, Quill.sources.USER);
        } else if (range.length > 0) {
            quill.formatText(range.index, range.length, 'link', false, Quill.sources.USER);
        }
    }
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
quill.root.addEventListener('paste', async (e) => {
    const items = e.clipboardData && e.clipboardData.items;
    if (!items) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.stopPropagation();
            e.preventDefault();
            const file = item.getAsFile();
            if (file) await uploadImageFile(file);
            return;
        }
    }

    // Auto-linkify: if the pasted text is purely a URL, insert it as a link
    const text = e.clipboardData.getData('text/plain').trim();
    if (text && /^https?:\/\/\S+$/.test(text)) {
        e.preventDefault();
        const range = quill.getSelection(true);
        const idx   = range ? range.index : quill.getLength();
        // If there's a selection, replace it; otherwise insert at cursor
        if (range && range.length > 0) {
            quill.deleteText(range.index, range.length, Quill.sources.USER);
        }
        quill.insertText(idx, text, 'link', text, Quill.sources.USER);
        quill.setSelection(idx + text.length, 0, Quill.sources.SILENT);
        // Set target=_blank
        setTimeout(() => {
            quill.root.querySelectorAll('a[href="' + CSS.escape(text) + '"]').forEach(a => {
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
            });
        }, 50);
        return;
    }
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