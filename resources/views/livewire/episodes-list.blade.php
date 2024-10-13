<?php

use App\Models\Episode;
use App\Models\Show;
use App\Models\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new
#[Layout('layouts.app')]
class extends Component {
    #[\Livewire\Attributes\Url]
    public ?int $selectedShowId = null;

    public function with()
    {
        $viewsForSelectedShowQuery = View::with('episode');
        if ($this->selectedShowId) {
            $viewsForSelectedShowQuery->whereHas('episode', fn(Builder $query) => $query->where('show_id', $this->selectedShowId));
        }
        $viewsForSelectedShow = $viewsForSelectedShowQuery->get();

        return [
            'shows' => Show::all(),
            'selectedShow' => $this->selectedShowId ? Show::where('id', $this->selectedShowId)->first() : null,
            'episodes' => Episode::orderBy('order')
                ->when($this->selectedShowId, fn(Builder $query, int $selectedShowId) => $query->where('show_id', $selectedShowId))
                ->with(['show', 'views'])
                ->get(),
            'viewsForSelectedShow' => $viewsForSelectedShow,
        ];
    }

    public function selectShow(Show $show)
    {
        if ($this->selectedShowId === $show->id) {
            $this->selectedShowId = null;
            return;
        }

        $this->selectedShowId = $show->id;
    }

    public function watchEpisode(Episode $episode)
    {
        $episode->views()->create([
            'user_id' => Auth::id(),
        ]);
    }

    public function unwatchEpisode(Episode $episode)
    {
        $episode->views()->where('user_id', Auth::id())->delete();
    }
}; ?>

<div class="space-y-6">
    <x-slot:header>
        The Grey's Cinematic Universe
    </x-slot:header>

    <div class="mb-4">
        <ul class="flex flex-wrap gap-4">
            @foreach($shows as $show)
                <li>
                    <x-primary-button wire:click="selectShow({{ $show }})"
                                      class="{{ $show->id === $this->selectedShowId ? '!bg-indigo-400' : null }}">{{ $show->title }}</x-primary-button>
                </li>
            @endforeach
        </ul>
    </div>

    <x-heading-2>
        @if ($this->selectedShowId)
            {{ $selectedShow->title }}
        @else
            All shows
        @endif
        ({{ $episodes->count() }} episodes)
    </x-heading-2>

    @auth
        <div>
            <x-progress-bar :percentage="$viewsForSelectedShow->count() / $episodes->count() * 100"
                            show_percentage_label="true"
                            color="sky"/>
        </div>
    @endauth

    <ul
        class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
    >
        @foreach($episodes as $episode)
            <li class="flex items-start justify-between p-4">
                <div class="space-y-1">
                    <div class="text-lg font-semibold">{{ $episode->title }}</div>
                    <div class="flex gap-3 flex-wrap items-baseline text-sm">
                        <div
                            class="px-2 py-1 text-xs rounded-lg {{ $episode->show->color }}">{{ $episode->show->title }}</div>
                        <span
                            class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-800 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-200 ring-1 ring-inset ring-gray-500/10">
                            S {{ $episode->season }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-800 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-200 ring-1 ring-inset ring-gray-500/10">
                            E {{ $episode->episode_number }}
                         </span>
                        <time>{{ $episode->air_date->format('F j, Y') }}</time>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    @auth
                        <div class="text-sm font-medium">
                            @if ($view = $episode->views()->where('user_id', Auth::id())->first())
                                <button wire:click="unwatchEpisode({{ $episode->id }})"
                                        class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                    Watched {{ $view->created_at->diffForHumans() }}
                                </button>
                            @else
                                <button wire:click="watchEpisode({{ $episode->id }})"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                    Not watched yet
                                </button>
                            @endif
                        </div>
                    @endauth

                    <div class="text-sm">{{ $episode->views->count() }} views</div>
                </div>
            </li>
        @endforeach
    </ul>
</div>
