@props(['status' => null, 'enum' => null, 'label' => null, 'color' => null])

@php
    $statusVal = $enum ?? $status ?? null;
    $labelText = $label;
    $colorClass = $color;

    if ($statusVal instanceof \UnitEnum) {
        if (method_exists($statusVal, 'label')) {
            $labelText = $labelText ?? $statusVal->label();
        }
        if (method_exists($statusVal, 'color')) {
            $colorName = $statusVal->color();
            $colorClass = match($colorName) {
                'success', 'emerald', 'green' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'warning', 'amber', 'yellow' => 'bg-amber-50 text-amber-700 border-amber-200',
                'danger', 'rose', 'red' => 'bg-rose-50 text-rose-700 border-rose-200',
                'info', 'sky', 'blue' => 'bg-sky-50 text-sky-700 border-sky-200',
                'purple', 'violet' => 'bg-purple-50 text-purple-700 border-purple-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
            };
        }
    }

    $labelText = $labelText ?? (string) ($statusVal ?? '-');
    $colorClass = $colorClass ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {$colorClass}"]) }}>
    {{ $labelText }}
</span>
