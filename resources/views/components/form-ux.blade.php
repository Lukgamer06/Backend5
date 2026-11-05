<script>
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const confirmation = form.dataset.confirm;

        if (confirmation && form.dataset.confirmed !== 'true') {
            event.preventDefault();

            if (!window.confirm(confirmation)) {
                return;
            }

            form.dataset.confirmed = 'true';
            form.requestSubmit(event.submitter instanceof HTMLElement ? event.submitter : undefined);

            return;
        }

        const submitButton = event.submitter instanceof HTMLButtonElement
            ? event.submitter
            : form.querySelector('button[type="submit"], input[type="submit"]');

        if (!(submitButton instanceof HTMLButtonElement)) {
            return;
        }

        const label = submitButton.dataset.loadingText || 'Procesando...';
        const spinner = document.createElement('span');
        const text = document.createElement('span');

        spinner.className = 'spinner-border spinner-border-sm';
        spinner.setAttribute('aria-hidden', 'true');
        text.textContent = label;

        submitButton.replaceChildren(spinner, document.createTextNode(' '), text);
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        form.setAttribute('aria-busy', 'true');
    });
</script>
