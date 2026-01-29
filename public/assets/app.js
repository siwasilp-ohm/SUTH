document.querySelectorAll('details').forEach((detail) => {
    detail.addEventListener('toggle', () => {
        if (detail.open) {
            detail.classList.add('open');
        } else {
            detail.classList.remove('open');
        }
    });
});

const scoreSelects = document.querySelectorAll('select[data-pillar]');
const totalScoreEl = document.getElementById('total-score');
const pillarTotals = document.querySelectorAll('[data-pillar-total]');

const updateScores = () => {
    const totals = {};
    let overall = 0;
    scoreSelects.forEach((select) => {
        const level = parseFloat(select.value || '0');
        const maxScore = parseFloat(select.dataset.maxScore || '0');
        const pillar = select.dataset.pillar;
        const points = level * maxScore;
        totals[pillar] = (totals[pillar] || 0) + points;
        overall += points;
    });

    if (totalScoreEl) {
        totalScoreEl.textContent = overall.toFixed(2);
    }
    pillarTotals.forEach((el) => {
        const key = el.dataset.pillarTotal;
        el.textContent = (totals[key] || 0).toFixed(2);
    });
};

if (scoreSelects.length) {
    updateScores();
    scoreSelects.forEach((select) => select.addEventListener('change', updateScores));
}
