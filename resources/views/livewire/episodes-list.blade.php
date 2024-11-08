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
    #[\Livewire\Attributes\Url]
    public ?int $selectedSeason = null;
    #[\Livewire\Attributes\Url]
    public ?int $selectedEpisode = null;

    public function with()
    {
        $viewsForSelectedShowQuery = View::with('episode')
            ->when($this->selectedShowId, fn(Builder $query) => $query->whereHas('episode', fn(Builder $query) => $query->where('show_id', $this->selectedShowId)));
        $viewsForSelectedShow = $viewsForSelectedShowQuery->get();

        return [
            'shows' => Show::all(),
            'selectedShow' => $this->selectedShowId ? Show::where('id', $this->selectedShowId)->first() : null,
            'episodes' => Episode::orderBy('order')
                ->when($this->selectedShowId, fn(Builder $query, int $selectedShowId) => $query->where('show_id', $selectedShowId))
                ->when($this->selectedSeason, fn(Builder $query, int $selectedSeason) => $query->where('season', $selectedSeason))
                ->with(['show', 'views'])
                ->get(),
            'viewsForSelectedShow' => $viewsForSelectedShow,
            'seasons' => $this->selectedShowId
                ? Episode::where('show_id', $this->selectedShowId)->max('season')
                : null
        ];
    }

    public function selectShow(Show $show)
    {
        if ($this->selectedShowId === $show->id) {
            $this->selectedShowId = null;
            $this->dispatch('view-changed', ['selectedEpisodeId' => $this->selectedEpisode]);
        } else {
            $this->selectedShowId = $show->id;
        }

        $this->selectedSeason = null;
    }

    public function selectSeason(int $season)
    {
        if ($this->selectedSeason === $season) {
            $this->selectedSeason = null;
            $this->dispatch('view-changed', ['selectedEpisodeId' => $this->selectedEpisode]);
            return;
        }

        $this->selectedSeason = $season;
    }

    public function selectEpisode(int $episode)
    {
        if ($this->selectedEpisode === $episode) {
            $this->selectedEpisode = null;
            return;
        }

        $this->selectedEpisode = $episode;
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
        <ul class="flex flex-wrap gap-2">
            @foreach($shows as $show)
                <li>
                    <x-primary-button wire:click="selectShow({{ $show }})"
                                      class="{{ $show->id === $this->selectedShowId ? '!bg-indigo-400' : null }}">{{ $show->title }}</x-primary-button>
                </li>
            @endforeach
        </ul>

        @if ($this->selectedShowId)
            <ul class="mt-4 flex flex-wrap gap-2">
                @for($i = 1; $i <= $seasons; $i++)
                    <li>
                        <x-primary-button wire:click="selectSeason({{ $i }})"
                                          class="{{ $this->selectedSeason == $i ? '!bg-indigo-400' : null }}">
                            Season {{ $i }}
                        </x-primary-button>
                    </li>
                @endfor
            </ul>
        @endif
    </div>

    <x-heading-2>
        @if ($this->selectedShowId)
            {{ $selectedShow->title }}
            @if ($this->selectedSeason)
                Season {{ $this->selectedSeason }}
            @else
                (All Seasons)
            @endif
        @else
            All shows
        @endif
        ({{ $episodes->count() }} episodes)
    </x-heading-2>

    @auth
        <div class="">
            <div class="text-gray-800 dark:text-gray-200">{{ $viewsForSelectedShow->count() }}
                / {{ $episodes->count() }} episodes watched
                ({{ round($viewsForSelectedShow->count() / $episodes->count(), 2) * 100 }}%)
            </div>
        </div>
    @endauth

    <ul
        class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
    >
        @foreach($episodes as $episode)
            <li @class([
            "flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer",
            $this->selectedEpisode === $episode->id ? 'bg-gray-50 dark:bg-gray-800' : null,
            ])
                wire:click="selectEpisode({{ $episode->id }})"
                id="episode-{{  $episode->id }}">
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

    @script
    <script>
        Livewire.on('view-changed', () => {
            setTimeout(() => {
                const episode = document.getElementById(`episode-{{ $this->selectedEpisode }}`);
                episode.scrollIntoView({behavior: 'smooth', block: 'center'})
            }, 1500)
        })
    </script>
    @endscript
</div>
