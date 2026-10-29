@use('App\Support\Money')

<div class="insight-grid">
    <section class="panel" aria-labelledby="low-stock-title">
        <h2 class="panel-title" id="low-stock-title">Low-stock alerts</h2>
        @forelse ($lowStock as $item)
            <div class="feed-row">
                <div class="feed-main">
                    <span class="feed-title">{{ $item->name }}</span>
                    <span class="feed-meta">Minimum level {{ $item->minStockLevel }}</span>
                </div>
                <span class="badge-low">{{ $item->stockQuantity }} left</span>
            </div>
        @empty
            <p class="feed-empty">Nothing is running low.</p>
        @endforelse
    </section>

    <section class="panel" aria-labelledby="recent-title">
        <h2 class="panel-title" id="recent-title">Recent stock movements</h2>
        @forelse ($recentMovements as $movement)
            @php($inbound = $movement->skuType === 'inbound')
            <div class="feed-row">
                <div class="feed-main">
                    <span class="feed-title">
                        {{ $movement->product?->name }}
                        <span @class(['badge-type', 'is-in' => $inbound, 'is-out' => ! $inbound])>
                            {{ $inbound ? 'Restock' : 'Sale' }} · {{ $movement->skuQuantity }}
                        </span>
                    </span>
                    <span class="feed-meta">
                        by {{ $movement->user?->name }} · {{ $movement->created_at->format('d M Y, H:i') }}
                        @if ($movement->skuNote) · {{ $movement->skuNote }} @endif
                    </span>
                </div>
                <span class="feed-amount">{{ $inbound ? '−' : '+' }}{{ Money::format($movement->totalAmount) }}</span>
            </div>
        @empty
            <p class="feed-empty">No stock movements recorded yet.</p>
        @endforelse
    </section>
</div>
