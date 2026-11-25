@props(['title', 'value', 'icon', 'tone' => 'blue'])

<div class="card stat-card border-0 shadow-sm h-100">
    <div class="card-body">
        <div>
            <p class="stat-label">{{ $title }}</p>
            <p class="stat-value">{{ number_format((int) $value) }}</p>
        </div>
        <span class="stat-icon stat-icon-{{ $tone }}" aria-hidden="true">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    </div>
</div>
