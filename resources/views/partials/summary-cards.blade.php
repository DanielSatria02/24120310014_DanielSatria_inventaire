@use('App\Support\Money')

<section class="summary-grid" aria-label="Financial summary">
    <article class="summary-card">
        <span class="summary-label">Revenue</span>
        <strong class="summary-value">{{ Money::format($revenue) }}</strong>
        <span class="summary-hint">From outbound movements</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Expenses</span>
        <strong class="summary-value">{{ Money::format($expenses) }}</strong>
        <span class="summary-hint">From inbound movements</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Net profit / loss</span>
        <strong @class(['summary-value', 'is-negative' => $net < 0, 'is-positive' => $net > 0])>{{ Money::format($net) }}</strong>
        <span class="summary-hint">Revenue minus expenses</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Inventory valuation</span>
        <strong class="summary-value">{{ Money::format($valuation) }}</strong>
        <span class="summary-hint">Stock × latest purchase price</span>
    </article>
</section>
