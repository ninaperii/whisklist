for (const picker of document.querySelectorAll('[data-search-picker]')) {
    const search = picker.querySelector('[data-picker-search]');
    const empty = picker.querySelector('[data-picker-empty]');
    const selected = picker.querySelector('[data-picker-selected]');
    const template = selected.querySelector('template');
    const options = [...picker.querySelectorAll('[data-picker-option]')].map((element) => ({
        element,
        checkbox: element.querySelector('input'),
        name: element.dataset.pickerOption,
    }));

    const filterOptions = () => {
        const term = search.value.trim().toLocaleLowerCase();
        let hasMatches = false;
        for (const { element, name } of options) {
            element.hidden = !name.toLocaleLowerCase().includes(term);
            hasMatches ||= !element.hidden;
        }
        empty.hidden = hasMatches;
    };

    const showSelections = () => {
        selected.querySelectorAll('button').forEach((button) => button.remove());
        for (const { checkbox, name } of options) {
            if (!checkbox.checked) {
                continue;
            }
            const chip = template.content.firstElementChild.cloneNode(true);
            chip.querySelector('[data-picker-chip-label]').textContent = checkbox.hasAttribute('data-automatic-filter')
                ? `${name} (from preferences)`
                : name;
            chip.setAttribute('aria-label', `Remove ${name}`);
            chip.addEventListener('click', () => {
                checkbox.click();
                search.focus();
            });
            selected.append(chip);
        }
    };

    picker.querySelector('[data-picker-search-label]').hidden = false;
    search.addEventListener('input', filterOptions);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });
    // Category and ingredient exclusions can also change selections in another picker.
    const form = picker.closest('form');
    document.addEventListener('change', (event) => {
        if (event.target.form === form && event.target.matches('[data-picker-option] input')) {
            showSelections();
        }
    });
    showSelections();
}

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
    const sidebarToggle = document.querySelector('[data-toggle-filter-sidebar]');
    const filterLayout = recipeFilters.closest('[data-filter-layout]');
    if (sidebarToggle && filterLayout) {
        sidebarToggle.hidden = false;
        sidebarToggle.addEventListener('click', () => {
            const collapsed = filterLayout.toggleAttribute('data-filters-collapsed');
            sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
            sidebarToggle.textContent = collapsed ? 'Show filters →' : '← Hide filters';
        });
    }

    const panel = recipeFilters.querySelector('[data-filter-panel]');
    if (panel && window.matchMedia('(max-width: 1023px)').matches) {
        panel.open = false;
    }

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

    const hasExcludedParent = (id) => {
        let parent = categoryInputs.get(included).get(id).dataset.categoryParent;
        while (parent) {
            const ancestor = categoryInputs.get(excluded).get(parent);
            if (ancestor.checked) {
                return true;
            }
            parent = ancestor.dataset.categoryParent;
        }
        return false;
    };

    const refreshParentStates = () => {
        for (const checkbox of categoryInputs.get(included).values()) {
            checkbox.disabled = hasExcludedParent(checkbox.value);
        }
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
            if (checked && checkbox.name === included && id !== checkbox.value
                && (opposite.get(id).checked || hasExcludedParent(id))) {
                continue;
            }
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

    const preferencesToggle = recipeFilters.querySelector('[name="use_preferences"]');
    if (preferencesToggle) {
        const saved = [...recipeFilters.querySelectorAll('[data-saved-filter]')];
        const manual = new Set(saved.filter((input) => input.checked && !input.hasAttribute('data-automatic-filter')));

        preferencesToggle.addEventListener('change', () => {
            for (const input of saved) {
                const conflictingInclude = input.dataset.savedFilter === 'exclude_ingredients'
                    && recipeFilters.querySelector(`input[name="ingredients[]"][value="${CSS.escape(input.value)}"]:checked`);
                const automatic = preferencesToggle.checked && !manual.has(input) && !conflictingInclude;
                input.toggleAttribute('data-automatic-filter', automatic);
                input.checked = automatic || manual.has(input);
                if (automatic) {
                    input.closest('details').open = true;
                }
            }
            for (const input of saved) {
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        recipeFilters.addEventListener('change', (event) => {
            const input = event.target;
            for (const option of manual) {
                if (!option.checked) {
                    manual.delete(option);
                }
            }
            if (!input.hasAttribute('data-saved-filter') || !input.checked) {
                return;
            }
            if (preferencesToggle.checked && !manual.has(input)) {
                input.setAttribute('data-automatic-filter', '');
            } else if (!preferencesToggle.checked) {
                manual.add(input);
            }
        });

        recipeFilters.addEventListener('formdata', ({ formData }) => {
            if (!preferencesToggle.checked) {
                return;
            }
            for (const field of ['dietary_tags', 'exclude_ingredients']) {
                const name = `${field}[]`;
                const preferences = saved.filter((input) => input.dataset.savedFilter === field);
                const automatic = preferences.filter((input) => input.hasAttribute('data-automatic-filter')).map((input) => input.value);
                const values = formData.getAll(name);
                formData.delete(name);
                for (const value of values.filter((value) => !automatic.includes(value))) {
                    formData.append(name, value);
                }
                for (const input of preferences.filter((input) => !input.checked)) {
                    formData.append(`skip_${name}`, input.value);
                }
            }
        });
    }
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
const editors = [...document.querySelectorAll('[data-recipe-editor], [data-profile-editor]')];
if (editors.length) {
    const leaveDialog = document.querySelector('#leave-recipe-editor');
    const publishDialog = document.querySelector('#publish-recipe');
    let publishButton;
    let publishConfirmed = false;
    const readFields = (form) => JSON.stringify([...new FormData(form)].filter(([, value]) => typeof value === 'string'));
    const initialFields = new Map(editors.map((form) => [form, readFields(form)]));
    const hasChanges = (except) => editors.some((form) => form !== except && (
        form.hasAttribute('data-unsaved-input')
        || form.querySelector('input[type="file"]')?.files.length > 0
        || readFields(form) !== initialFields.get(form)
    ));
    let leaving = false;
    let continueNavigation;

    const confirmNavigation = (event, navigate, except) => {
        if (leaving || !hasChanges(except)) {
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

    recipeEditor?.addEventListener('submit', (event) => {
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
            confirmNavigation(event, () => form.requestSubmit(event.submitter ?? undefined), form);
            if (!event.defaultPrevented) {
                leaving = true;
            }
        }
    });

    window.addEventListener('pageshow', (event) => {
        leaving = false;
        const key = `published-editor:${window.location.pathname}`;
        const editorPath = sessionStorage.getItem(key);
        const isHistoryNavigation = event.persisted || performance.getEntriesByType('navigation')[0]?.type === 'back_forward';

        if (recipeEditor && editorPath && isHistoryNavigation) {
            leaving = true;
            sessionStorage.removeItem(key);
            window.location.replace(editorPath);
        }
    });
}
