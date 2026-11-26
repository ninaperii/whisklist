const thumbnailPicker = document.querySelector('[data-thumbnail-picker]');
if (thumbnailPicker) {
    const input = thumbnailPicker.querySelector('input[type="file"]');
    const preview = thumbnailPicker.querySelector('[data-thumbnail-preview]');
    const image = preview.querySelector('img');
    const status = thumbnailPicker.querySelector('[role="status"]');
    const savedSource = image.getAttribute('src');
    const savedStatus = status.textContent;
    let previewUrl;

    input.addEventListener('change', () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = undefined;
        }

        const file = input.files[0];
        if (file) {
            previewUrl = URL.createObjectURL(file);
            image.src = previewUrl;
            preview.hidden = false;
            status.textContent = `${file.name} selected. Save the recipe to keep this thumbnail.`;
        } else {
            if (savedSource) {
                image.src = savedSource;
            } else {
                image.removeAttribute('src');
            }
            preview.hidden = !savedSource;
            status.textContent = savedStatus;
        }
    });
}

const privacyDialog = document.querySelector('#make-recipe-private');
if (privacyDialog) {
    let pendingForm;
    let confirmedForm;

    for (const form of document.querySelectorAll('[data-make-private]')) {
        form.addEventListener('submit', (event) => {
            if (form === confirmedForm) {
                return;
            }
            event.preventDefault();
            pendingForm = form;
            privacyDialog.returnValue = '';
            privacyDialog.showModal();
        });
    }

    privacyDialog.addEventListener('close', () => {
        if (privacyDialog.returnValue === 'confirm' && pendingForm) {
            confirmedForm = pendingForm;
            confirmedForm.requestSubmit();
            confirmedForm = undefined;
        }
        pendingForm = undefined;
    });
}

for (const form of document.querySelectorAll('[data-confirm-delete]')) {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmDelete)) {
            event.preventDefault();
        }
    });
}

const recipeFilters = document.querySelector('[data-recipe-filters]');
if (recipeFilters) {
    const included = 'categories[]';
    const excluded = 'exclude_categories[]';
    const categoryInputs = new Map([[included, new Map()], [excluded, new Map()]]);
    const children = new Map();

    for (const checkbox of recipeFilters.querySelectorAll('[data-category-parent]')) {
        categoryInputs.get(checkbox.name).set(checkbox.value, checkbox);
        if (checkbox.name === included) {
            const parent = checkbox.dataset.categoryParent;
            if (!children.has(parent)) {
                children.set(parent, []);
            }
            children.get(parent).push(checkbox.value);
        }
    }

    const refreshParentStates = () => {
        for (const checkboxes of categoryInputs.values()) {
            // The form lists parents before children, so read child states first.
            for (const checkbox of [...checkboxes.values()].reverse()) {
                const hasSelectedChild = (children.get(checkbox.value) ?? []).some((id) => {
                    const child = checkboxes.get(id);
                    return child.checked || child.indeterminate;
                });
                checkbox.indeterminate = !checkbox.checked && hasSelectedChild;
            }
        }
    };

    recipeFilters.addEventListener('change', (event) => {
        const checkbox = event.target;
        if (checkbox.checked && ['ingredients[]', 'exclude_ingredients[]'].includes(checkbox.name)) {
            const oppositeName = checkbox.name === 'ingredients[]' ? 'exclude_ingredients[]' : 'ingredients[]';
            const opposite = recipeFilters.querySelector(`input[name="${oppositeName}"][value="${CSS.escape(checkbox.value)}"]`);
            if (opposite) {
                opposite.checked = false;
            }
            return;
        }

        if (!categoryInputs.has(checkbox.name)) {
            return;
        }

        const checked = checkbox.checked;
        const current = categoryInputs.get(checkbox.name);
        const opposite = categoryInputs.get(checkbox.name === included ? excluded : included);
        const pending = [checkbox.value];

        while (pending.length) {
            const id = pending.pop();
            current.get(id).checked = checked;
            if (checked) {
                opposite.get(id).checked = false;
            }
            for (const child of children.get(id) ?? []) {
                pending.push(child);
            }
        }
        refreshParentStates();
    });

    refreshParentStates();
}

for (const list of document.querySelectorAll('[data-draft-list]')) {
    const rows = list.querySelector('[data-rows]');
    const template = list.querySelector('[data-row-template]');
    const addButton = list.querySelector('[data-add]');

    if (!rows || !template || !addButton) {
        continue;
    }

    const refreshRows = () => {
        const items = rows.querySelectorAll('[data-row]');
        for (const [index, row] of items.entries()) {
            for (const field of row.querySelectorAll('[data-name]')) {
                field.name = field.dataset.name.replace('__index__', index);
            }
            const label = row.querySelector('[data-step-label]');
            if (label) {
                label.textContent = `Step ${index + 1}`;
            }
        }
        addButton.disabled = items.length >= 100;
    };

    addButton.addEventListener('click', () => {
        rows.append(template.content.cloneNode(true));
        refreshRows();
        rows.lastElementChild.querySelector('select, textarea')?.focus();
    });

    rows.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove]');
        if (button) {
            const row = button.closest('[data-row]');
            const next = row.nextElementSibling ?? row.previousElementSibling;
            row.remove();
            refreshRows();
            (next?.querySelector('[data-remove]') ?? addButton).focus();
        }
    });

    refreshRows();
}

const publicationNotice = document.querySelector('[data-published-from]');
if (publicationNotice) {
    const { publishedFrom, publishedEditor } = publicationNotice.dataset;
    sessionStorage.setItem(`published-editor:${publishedFrom}`, publishedEditor);
}

const recipeEditor = document.querySelector('[data-recipe-editor]');
if (recipeEditor) {
    const leaveDialog = document.querySelector('#leave-recipe-editor');
    const publishDialog = document.querySelector('#publish-recipe');
    let publishButton;
    let publishConfirmed = false;
    const readFields = () => JSON.stringify([...new FormData(recipeEditor)].filter(([, value]) => typeof value === 'string'));
    const initialFields = readFields();
    const hasChanges = () => recipeEditor.hasAttribute('data-unsaved-input')
        || recipeEditor.querySelector('input[type="file"]').files.length > 0
        || readFields() !== initialFields;
    let leaving = false;
    let continueNavigation;

    const confirmNavigation = (event, navigate) => {
        if (leaving || !hasChanges()) {
            return;
        }
        event.preventDefault();
        continueNavigation = navigate;
        leaveDialog.returnValue = '';
        leaveDialog.showModal();
    };

    leaveDialog.addEventListener('close', () => {
        const navigate = continueNavigation;
        continueNavigation = undefined;
        if (leaveDialog.returnValue === 'leave' && navigate) {
            leaving = true;
            navigate();
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (!leaving && hasChanges()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    recipeEditor.addEventListener('submit', (event) => {
        if (publishDialog && event.submitter?.value === 'publish' && !publishConfirmed) {
            event.preventDefault();
            publishButton = event.submitter;
            publishDialog.returnValue = '';
            publishDialog.showModal();
            return;
        }
        leaving = true;
    });

    publishDialog?.addEventListener('close', () => {
        if (publishDialog.returnValue === 'publish' && publishButton) {
            publishConfirmed = true;
            recipeEditor.requestSubmit(publishButton);
            publishConfirmed = false;
        }
        publishButton = undefined;
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey
            || event.shiftKey || event.altKey || link.hasAttribute('download')
            || (link.target && link.target !== '_self') || link.getAttribute('href').startsWith('#')) {
            return;
        }

        confirmNavigation(event, () => window.location.assign(link.href));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!event.defaultPrevented && form !== recipeEditor && form.method !== 'dialog') {
            confirmNavigation(event, () => form.requestSubmit(event.submitter ?? undefined));
        }
    });

    window.addEventListener('pageshow', (event) => {
        leaving = false;
        const key = `published-editor:${window.location.pathname}`;
        const editorPath = sessionStorage.getItem(key);
        const isHistoryNavigation = event.persisted || performance.getEntriesByType('navigation')[0]?.type === 'back_forward';

        if (editorPath && isHistoryNavigation) {
            leaving = true;
            sessionStorage.removeItem(key);
            window.location.replace(editorPath);
        }
    });
}
