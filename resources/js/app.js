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
