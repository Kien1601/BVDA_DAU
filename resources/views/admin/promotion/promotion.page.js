const typeSelect = document.querySelector('[data-discount-type]');
const valueInput = document.querySelector('[data-discount-value]');

if (typeSelect && valueInput) {
    const apply = () => {
        const percent = typeSelect.value === 'percent';
        valueInput.min = percent ? 1 : 1000;
        valueInput.max = percent ? 100 : 10000000;
        valueInput.step = percent ? 1 : 1000;
        valueInput.placeholder = percent ? '1 – 100' : 'VD: 50000';
    };

    typeSelect.addEventListener('change', apply);
    apply();
}