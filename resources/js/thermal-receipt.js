const receipt = document.querySelector('.thermal-receipt');

if (receipt) {
    const pageStyle = document.createElement('style');
    document.head.append(pageStyle);

    const sizePaper = () => {
        // CSS pixels use 96 dpi; add a little room for print rounding.
        const heightMm = Math.ceil(receipt.getBoundingClientRect().height * 25.4 / 96) + 2;
        pageStyle.textContent = `@page { size: 80mm ${Math.max(40, heightMm)}mm; margin: 0; }`;
    };

    window.addEventListener('beforeprint', sizePaper);

    const prepareReceipt = async () => {
        await document.fonts.ready;
        sizePaper();

        if (document.body.dataset.autoPrint === 'true') {
            document.body.dataset.autoPrint = 'false';
            const url = new URL(window.location.href);
            url.searchParams.delete('print');
            window.history.replaceState(window.history.state, '', url);
            requestAnimationFrame(() => window.print());
        }
    };

    if (document.readyState === 'complete') {
        prepareReceipt();
    } else {
        window.addEventListener('load', prepareReceipt, { once: true });
    }
}
