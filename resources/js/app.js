import './bootstrap';

const drawer = document.getElementById('side-menu');

if (drawer) {
    const setMenu = (open) => {
        document.body.classList.toggle('menu-open', open);
        drawer.inert = !open;
        drawer.setAttribute('aria-hidden', String(!open));
        document
            .querySelectorAll('[data-menu-toggle]')
            .forEach((button) => button.setAttribute('aria-expanded', String(open)));

        if (open) {
            drawer.querySelector('a, button')?.focus();
        } else {
            document.querySelector('[data-menu-toggle]')?.focus();
        }
    };

    document
        .querySelectorAll('[data-menu-toggle]')
        .forEach((button) => button.addEventListener('click', () => setMenu(true)));
    document
        .querySelectorAll('[data-menu-close]')
        .forEach((element) => element.addEventListener('click', () => setMenu(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('menu-open')) {
            setMenu(false);
        }
    });
}

document.querySelectorAll('[data-movement-form]').forEach((form) => {
    const type = form.querySelector('[data-movement-type]');
    const inboundOnly = form.querySelector('[data-inbound-only]');
    const outboundOnly = form.querySelector('[data-outbound-only]');
    const price = form.querySelector('[data-movement-price]');

    const sync = () => {
        const inbound = type.value === 'inbound';
        inboundOnly.hidden = !inbound;
        outboundOnly.hidden = inbound;
        price.required = inbound;
    };

    type.addEventListener('change', sync);
    sync();
});

document.querySelectorAll('[data-dialog-open]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById(button.dataset.dialogOpen)?.showModal();
    });
});

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
    }
});
