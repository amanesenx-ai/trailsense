// TrailSense — progressive enhancement only; every flow works without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
    // F9 AC9.3: copy briefing (textarea stays selectable without JS)
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copy);
            const status = document.querySelector('.copy-status');
            if (!target) return;
            try {
                await navigator.clipboard.writeText(target.value);
            } catch (e) {
                target.select();
                document.execCommand('copy');
            }
            if (status) status.textContent = 'Briefing copied. Paste it in your group chat.';
        });
    });

    // AC2.1: duration follows the chosen trail's estimate unless the user typed one
    const trailSelect = document.querySelector('[data-duration-source]');
    const duration = document.getElementById('duration_min');
    if (trailSelect && duration) {
        let touched = duration.value !== '';
        duration.addEventListener('input', () => { touched = true; });
        trailSelect.addEventListener('change', () => {
            const opt = trailSelect.selectedOptions[0];
            if (!touched && opt && opt.dataset.duration) duration.value = opt.dataset.duration;
        });
    }

    // Confirm destructive actions
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
        });
    });
});
