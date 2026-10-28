(() => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const sidebar = document.getElementById('sidebar');
    const scrim = document.querySelector('[data-menu-close]');
    const closeMenu = () => {
        document.body.classList.remove('menu-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    };
    if (toggle) toggle.addEventListener('click', () => {
        const open = !document.body.classList.contains('menu-open');
        document.body.classList.toggle('menu-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    });
    if (scrim) scrim.addEventListener('click', closeMenu);
    document.querySelectorAll('[data-dismiss]').forEach((button) => button.addEventListener('click', () => button.closest('.flash')?.remove()));
    document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm') || 'Vai tiešām turpināt?';
        if (!window.confirm(message)) event.preventDefault();
    }));

    const roleSelect = document.querySelector('[data-role-select]');
    const adminField = document.querySelector('[data-admin-code-field]');
    const adminInput = document.getElementById('admin_invite_code');
    const syncRole = () => {
        if (!roleSelect || !adminField) return;
        const show = roleSelect.value === 'admin';
        adminField.hidden = !show;
        if (adminInput) adminInput.required = show;
    };
    if (roleSelect) {
        roleSelect.addEventListener('change', syncRole);
        syncRole();
    }

    document.querySelectorAll('[data-recurrence-select]').forEach((select) => {
        const form = select.closest('form');
        const weekdays = form?.querySelector('[data-weekdays]');
        const ranges = form?.querySelector('[data-month-ranges]');
        const update = () => {
            if (weekdays) weekdays.hidden = select.value !== 'custom_weekdays';
            if (ranges) ranges.hidden = select.value !== 'month_ranges';
        };
        select.addEventListener('change', update);
        update();
    });


    const stableMode = document.querySelector('[data-stable-mode]');
    const stableCreate = document.querySelector('[data-stable-create]');
    const stableJoin = document.querySelector('[data-stable-join]');
    const stableName = document.getElementById('stable_name');
    const stableCode = document.getElementById('stable_invite_code');
    const syncStableMode = () => {
        if (!stableMode) return;
        const create = stableMode.value === 'create';
        if (stableCreate) stableCreate.hidden = !create;
        if (stableJoin) stableJoin.hidden = create;
        if (stableName) stableName.required = create;
        if (stableCode) stableCode.required = !create;
    };
    if (stableMode) { stableMode.addEventListener('change', syncStableMode); syncStableMode(); }

    document.querySelectorAll('form[data-complete-note]').forEach((form) => form.addEventListener('submit', (event) => {
        const completed = form.querySelector('input[name="completed"]')?.value === '1';
        if (!completed) return;
        const note = window.prompt('Piezīme par uzdevuma izpildi (nav obligāta, līdz 500 rakstzīmēm):', '');
        if (note === null) { event.preventDefault(); return; }
        const noteField = form.querySelector('input[name="notes"]');
        if (noteField) noteField.value = note.trim().slice(0, 500);
    }));

    document.querySelectorAll('[data-calendar-more]').forEach((button) => {
        const extras = button.closest('.day-events')?.querySelectorAll('[data-calendar-extra]') ?? [];
        let expanded = false;

        button.addEventListener('click', () => {
            expanded = !expanded;
            extras.forEach((event) => { event.hidden = !expanded; });
            button.setAttribute('aria-expanded', String(expanded));
            button.textContent = expanded ? 'Rādīt mazāk' : `+ ${button.dataset.moreCount} vēl`;
        });
    });

    // Mobile landing page navigation stays small and keyboard-accessible.
    const landingToggle = document.querySelector('.landing-menu');
    const landingNav = document.querySelector('.landing-nav');
    if (landingToggle && landingNav) landingToggle.addEventListener('click', () => {
        landingNav.classList.toggle('open');
        landingToggle.setAttribute('aria-expanded', String(landingNav.classList.contains('open')));
    });

    const themeRoot = document.documentElement;
    const themeKey = 'stablecare-theme';
    let themeButton = document.querySelector('[data-theme-toggle]');
    if (!themeButton) {
        themeButton = document.createElement('button');
        themeButton.type = 'button';
        themeButton.className = 'theme-toggle';
        themeButton.setAttribute('data-theme-toggle', '');
        themeButton.innerHTML = '<span class="theme-toggle-icon" aria-hidden="true"></span><span class="theme-toggle-label"></span>';
        const themeHost = document.querySelector('.topbar-right, .landing-header') || document.body;
        themeHost.append(themeButton);
    }

    const updateThemeButton = () => {
        const isDark = themeRoot.dataset.theme === 'dark';
        const nextLabel = isDark ? 'Gaišais režīms' : 'Tumšais režīms';
        themeButton.querySelector('.theme-toggle-icon').textContent = isDark ? '☀' : '☾';
        themeButton.querySelector('.theme-toggle-label').textContent = nextLabel;
        themeButton.setAttribute('aria-label', `Pārslēgt uz ${nextLabel.toLowerCase()}`);
        themeButton.title = nextLabel;
        const themeColor = document.querySelector('meta[name="theme-color"]');
        if (themeColor) themeColor.content = isDark ? '#101814' : '#183e32';
    };

    themeButton.addEventListener('click', () => {
        const nextTheme = themeRoot.dataset.theme === 'dark' ? 'light' : 'dark';
        themeRoot.dataset.theme = nextTheme;
        try { window.localStorage.setItem(themeKey, nextTheme); } catch (error) { /* Keep the current page usable when storage is blocked. */ }
        updateThemeButton();
    });
    updateThemeButton();
})();
