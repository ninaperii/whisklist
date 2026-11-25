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
