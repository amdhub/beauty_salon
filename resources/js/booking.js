const bookingForm = document.querySelector('[data-booking-form]');

if (bookingForm) {
    const select = bookingForm.querySelector('[name="service_option"]');
    const summary = bookingForm.querySelector('[data-booking-summary]');
    const updateSummary = () => {
        const option = select.selectedOptions[0];
        summary.hidden = !option?.value;
        summary.querySelector('[data-booking-label]').textContent = option?.dataset.label ?? '';
        summary.querySelector('[data-booking-price]').textContent = option?.dataset.price ?? '';
        summary.querySelector('[data-booking-duration]').textContent = option?.dataset.duration ?? '';
    };
    select.addEventListener('change', updateSummary);
    updateSummary();
}

const whatsappLink = document.querySelector('[data-whatsapp-auto-open]');

if (whatsappLink) {
    const redirectTimer = window.setTimeout(() => {
        window.location.assign(whatsappLink.href);
    }, 1500);

    whatsappLink.addEventListener('click', () => window.clearTimeout(redirectTimer));
    window.addEventListener('pagehide', () => window.clearTimeout(redirectTimer), { once: true });
}
