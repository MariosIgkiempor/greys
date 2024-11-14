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

    public function mount()
    {
        if (Auth::guest()) {
            $this->selectedShowId = null;
            $this->selectedSeason = null;
            $this->selectedEpisode = null;
        }
    }

    public function with()
    {
        $viewsForSelectedShowQuery = View::with('episode')
            ->when($this->selectedShowId, fn(Builder $query) => $query->whereHas('episode', fn(Builder $query) => $query->where('show_id', $this->selectedShowId)));
        $viewsForSelectedShow = $viewsForSelectedShowQuery->get();

        return [
            'shows' => Show::all(),
            'selectedShow' => $this->selectedShowId ? Show::where('id', $this->selectedShowId)->first() : null,
            'viewsForSelectedShow' => $viewsForSelectedShow,

            'seasons' => $this->selectedShowId
                ? Episode::where('show_id', $this->selectedShowId)->max('season')
                : null,

            'episodes' => Episode::orderBy('order')
                ->when($this->selectedShowId, fn(Builder $query, int $selectedShowId) => $query->where('show_id', $selectedShowId))
                ->when($this->selectedSeason, fn(Builder $query, int $selectedSeason) => $query->where('season', $selectedSeason))
                ->with(['show', 'views'])
                ->get(),

            'theSelectedEpisode' => Episode::where('id', $this->selectedEpisode)->first(),
        ];
    }

    public function updated($property)
    {
        if ($property === 'selectedShowId') {
            $this->selectedSeason = null;
            $this->dispatch('view-changed', ['selectedEpisodeId' => $this->selectedEpisode]);
        }

        if ($property === 'selectedSeason') {
            $this->dispatch('view-changed', ['selectedEpisodeId' => $this->selectedEpisode]);
        }
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
        $this->selectEpisode($episode->id);
        $episode->watchEpisode();
    }

    public function unwatchEpisode(Episode $episode)
    {
        $this->selectEpisode($episode->id);
        $episode->unwatchEpisode();
    }

    public function watchAllEpisodesUntil(Episode $episode)
    {
        Episode::where('order', '<=', $episode->order)->each(fn(Episode $episode) => $episode->watchEpisode());
    }
}; ?>

<div class="space-y-6">
    <x-slot:header>
        The Grey's Cinematic Universe
    </x-slot:header>

    <div class="mb-4">
        <div class="grid gap-4 grid-cols-2">
            <x-mary-select label="Show"
                           placeholder="All Shows"
                           :options="$shows"
                           wire:model.live="selectedShowId"
                           option-label="title"
                           inline/>
            @if ($this->selectedShowId)
                <x-mary-select label="Season"
                               placeholder="All Seasons"
                               :options="collect(range(1, $seasons))->map(fn($season) => ['title' => 'Season '.$season, 'id' => $season])"
                               wire:model.live="selectedSeason"
                               option-label="title"
                               inline/>
            @endif
        </div>

    </div>

    <div class="flex gap-4">
        <x-mary-header separator class="flex-1">
            <x-slot:title>
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
            </x-slot:title>
            <x-slot:subtitle>
                ({{ $episodes->count() }} episodes)

                @auth
                    <div class="">
                        <div class="text-gray-800 dark:text-gray-200">{{ $viewsForSelectedShow->count() }}
                            / {{ $episodes->count() }} episodes watched
                            ({{ round($viewsForSelectedShow->count() / $episodes->count(), 2) * 100 }}%)
                        </div>
                    </div>
                @endauth
            </x-slot:subtitle>
        </x-mary-header>
    </div>

    @if ($this->selectedEpisode)
        <div class="sticky top-0 z-10">
            <x-mary-card separator
                         progress-indicator
            >
                <x-slot:title>
                    Selected
                    {{  $theSelectedEpisode->show->title }}
                    S{{ $theSelectedEpisode->season }}
                    E{{  $theSelectedEpisode->episode_number }}
                </x-slot:title>

                <x-mary-button wire:click="selectEpisode({{ $theSelectedEpisode->id }})" class="btn-sm btn-secondary">
                    De-select
                </x-mary-button>
                <x-mary-button label="Scroll" x-on:click="window.scrollToEpisode({{ $theSelectedEpisode->id }})"
                               class="btn-sm btn-outline"/>
                <x-mary-button label="Watch all episodes until this one"
                               wire:click="watchAllEpisodesUntil({{ $theSelectedEpisode->id }})"
                               class="btn-sm btn-primary"/>
            </x-mary-card>
        </div>
    @endif

    @foreach($episodes as $episode)
        <x-mary-list-item
            :item="$episode"
            no-separator
            no-hover
            id="{{ 'episode-'.$episode->id }}"
            @class([
                'bg-gray-700' => $episode->id === $this->selectedEpisode,
            ])
        >
            <x-slot:value>
                {{ $episode->title }}
            </x-slot:value>
            <x-slot:sub-value>
                <x-mary-badge :value="$episode->show->title"
                              :class="$episode->show->className()"
                />
                <x-mary-badge :value="'S'.$episode->season . ' E'. $episode->episode_number"/>
                {{ $episode->air_date->format('d M Y') }} &middot;
                {{ $episode->views->count() }} views
            </x-slot:sub-value>
            <x-slot:actions>
                @auth
                    @if ($view = $episode->views()->where('user_id', Auth::id())->first())
                        <div class=" flex gap-2 flex-wrap items-baseline
                ">
                            <div class="text-xs">Watched {{ $view->created_at->diffForHumans() }}</div>
                            <x-mary-button wire:click="unwatchEpisode({{ $episode->id }})" class="btn-sm btn-error">
                                Unwatch
                            </x-mary-button>
                        </div>
                    @else
                        <x-mary-button wire:click="watchEpisode({{ $episode->id }})" class="btn-sm btn-success">
                            Watch
                        </x-mary-button>
                    @endif

                    @if ($this->selectedEpisode === $episode->id)
                        <x-mary-button wire:click="selectEpisode({{ $episode->id }})" class="btn-sm btn-secondary">
                            De-select
                        </x-mary-button>
                    @else
                        <x-mary-button wire:click="selectEpisode({{ $episode->id }})" class="btn-sm btn-outline">
                            Select
                        </x-mary-button>
                    @endif
                @endauth
            </x-slot:actions>
        </x-mary-list-item>
    @endforeach

    @script
    <script>
        window.scrollToEpisode = function (episodeId) {
            const episode = document.getElementById(`episode-${episodeId}`);
            episode.scrollIntoView({behavior: 'smooth', block: 'center'})
        }
        Livewire.on('view-changed', () => {
            setTimeout(() => {
                scrollToEpisode({{ $this->selectedEpisode }})
            }, 500)
        })
    </script>
    @endscript
</div>
