(() => {
    const chevron = '<svg class="nx-select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    const database = '<svg class="nx-select-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/></svg>';
    const cloud = '<svg class="nx-select-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>';
    const check = '<svg class="nx-select-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>';

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const leading = (option) => {
        const flagUrl = option.dataset.flagUrl || '';
        const flag = option.dataset.flag || '';
        const provider = option.dataset.provider || '';
        if (flagUrl) return `<img class="nx-select-flag-image" src="${escapeHtml(flagUrl)}" alt="">`;
        if (flag) return `<span class="nx-select-flag">${escapeHtml(flag)}</span>`;
        return provider === 'aws' ? cloud : database;
    };

    function enhanceSelect(select) {
        if (!select || select.dataset.enhanced === '1') return;
        select.dataset.enhanced = '1';
        const root = document.createElement('div');
        root.className = 'nx-select-ready';
        select.parentNode.insertBefore(root, select);
        root.appendChild(select);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'nx-select-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-expanded', 'false');
        const menu = document.createElement('div');
        menu.className = 'nx-select-menu';
        menu.setAttribute('role', 'listbox');
        root.append(trigger, menu);

        const options = [...select.options];
        const renderSelected = () => {
            const option = select.selectedOptions[0] || options.find((item) => !item.disabled) || options[0];
            if (!option) return;
            const description = option.dataset.description || option.parentElement?.label || '';
            trigger.innerHTML = `<span class="nx-select-trigger-main">${leading(option)}<span class="nx-select-value"><strong>${escapeHtml(option.textContent)}</strong>${description ? `<small>${escapeHtml(description)}</small>` : ''}</span></span>${chevron}`;
        };

        const addOption = (option) => {
            const control = document.createElement('button');
            control.type = 'button';
            control.className = 'nx-select-option';
            control.setAttribute('role', 'option');
            control.setAttribute('aria-disabled', option.disabled ? 'true' : 'false');
            control.innerHTML = `<span class="nx-select-option-leading">${leading(option)}</span><span class="nx-select-option-copy"><strong>${escapeHtml(option.textContent)}</strong>${option.dataset.description ? `<small>${escapeHtml(option.dataset.description)}</small>` : ''}</span><span class="nx-select-option-check">${check}</span>`;
            const sync = () => {
                const selected = select.value === option.value;
                control.classList.toggle('selected', selected);
                control.setAttribute('aria-selected', selected ? 'true' : 'false');
            };
            sync();
            control.addEventListener('click', () => {
                if (option.disabled) return;
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                renderSelected();
                [...menu.querySelectorAll('.nx-select-option')].forEach((item) => item.classList.toggle('selected', item === control));
                root.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            });
            menu.appendChild(control);
        };

        [...select.children].forEach((child) => {
            if (child.tagName === 'OPTGROUP') {
                const group = document.createElement('div');
                group.className = 'nx-select-group';
                group.textContent = child.label;
                menu.appendChild(group);
                [...child.children].forEach(addOption);
            } else if (child.tagName === 'OPTION') addOption(child);
        });

        trigger.addEventListener('click', () => {
            const open = !root.classList.contains('open');
            document.querySelectorAll('.nx-select-ready.open').forEach((item) => item !== root && item.classList.remove('open'));
            root.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { root.classList.remove('open'); trigger.setAttribute('aria-expanded', 'false'); }
            if (event.key === 'ArrowDown') { event.preventDefault(); root.classList.add('open'); trigger.setAttribute('aria-expanded', 'true'); menu.querySelector('.nx-select-option:not([aria-disabled="true"])')?.focus(); }
        });
        menu.addEventListener('keydown', (event) => {
            const items = [...menu.querySelectorAll('.nx-select-option:not([aria-disabled="true"])')];
            const index = items.indexOf(document.activeElement);
            if (event.key === 'ArrowDown') { event.preventDefault(); items[Math.min(items.length - 1, index + 1)]?.focus(); }
            if (event.key === 'ArrowUp') { event.preventDefault(); items[Math.max(0, index - 1)]?.focus(); }
            if (event.key === 'Escape') { root.classList.remove('open'); trigger.focus(); }
        });
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) { root.classList.remove('open'); trigger.setAttribute('aria-expanded', 'false'); }
        });
        select.addEventListener('change', renderSelected);
        renderSelected();
    }

    window.NexoraInstallerUI = {
        enhanceSelects(root = document) {
            root.querySelectorAll('select[data-nx-select]').forEach(enhanceSelect);
        },
        setStatus(element, ok, message) {
            if (!element) return;
            element.className = `driver-health nx-ui-status ${ok ? 'ok' : 'bad'}`;
            const icon = ok
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>';
            element.innerHTML = `<span class="driver-health-icon">${icon}</span><span class="driver-health-copy">${escapeHtml(message)}</span>`;
        },
    };
})();
