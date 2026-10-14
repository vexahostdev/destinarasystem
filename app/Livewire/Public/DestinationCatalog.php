<?php

namespace App\Livewire\Public;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class DestinationCatalog extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public string $province = '';
    public string $sort = 'popular';

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'province' => ['except' => ''],
        'sort' => ['except' => 'popular'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingProvince(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'province', 'sort']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Destination::query()
            ->with(['category', 'province', 'packages', 'media', 'reviews'])
            ->where('status', DestinationStatus::PUBLISHED);

        if (! empty($this->search)) {
            $q = trim($this->search);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (! empty($this->category)) {
            $query->whereHas('category', function ($sub) {
                $sub->where('slug', $this->category);
            });
        }

        if (! empty($this->province)) {
            $query->whereHas('province', function ($sub) {
                $sub->where('name', $this->province);
            });
        }

        if ($this->sort === 'lowest_price') {
            $query->withMin('packages', 'price_per_pax')
                ->orderBy('packages_min_price_per_pax', 'asc');
        } elseif ($this->sort === 'highest_capacity') {
            $query->orderBy('default_daily_capacity', 'desc');
        } else {
            $query->orderBy('rating', 'desc')->latest();
        }

        $destinations = $query->paginate(12);

        $categories = DestinationCategory::where('is_active', true)->pluck('name', 'slug')->all();
        $provinces = Province::orderBy('name')->pluck('name')->all();

        return view('livewire.public.destination-catalog', [
            'destinations' => $destinations,
            'categories' => $categories,
            'provinces' => $provinces,
        ]);
    }
}
