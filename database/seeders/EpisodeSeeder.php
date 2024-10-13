<?php

namespace Database\Seeders;

use App\Models\Show;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class EpisodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file_contents = File::get(storage_path('episodes.txt'));
        $lines = collect(explode("\n", $file_contents));

        $order = 0;

        for ($i = 0; $i < $lines->count(); $i += 3) {
            if ($lines->get($i) === '') {
                continue;
            }

            [$showCode, $afterShowCode] = explode(' ', $lines->get($i), 2);
            $showCode = trim($showCode);
            $afterShowCode = trim($afterShowCode);
            [$seasonAndEpisodeCode, $episodeTitle] = explode(' ', $afterShowCode, 2);
            $seasonAndEpisodeCode = trim($seasonAndEpisodeCode);
            $episodeTitle = trim($episodeTitle);
            try {
                [$seasonNumber, $episodeNumber] = explode('x', $seasonAndEpisodeCode, 2);
            } catch (Throwable $e) {
            }

            $episodeTitle = Str::trim($episodeTitle);

            $show = Show::firstOrCreate(['code' => $showCode], [
                'title' => match ($showCode) {
                    'GA' => 'Grey\'s Anatomy',
                    'PP' => 'Private Practice',
                    'S19' => 'Station 19',
                    'SG:OC' => 'Seattle Grace: On Call',
                    'SG:MOH' => 'Seattle Grace: Message of Hope',
                    'GA:BT' => 'Grey\'s Anatomy: B-Team',
                    default => dd("Unknown show code: $showCode"),
                },
                'color' => match ($showCode) {
                    'GA' => 'bg-slate-500',
                    'PP' => 'bg-slate-400',
                    'S19' => 'bg-purple-300',
                    'SG:OC' => 'bg-gray-400',
                    'SG:MOH' => 'bg-gray-400',
                    'GA:BT' => 'bg-gray-400',
                    default => dd("Unknown show code: $showCode"),
                }]);

            $episodeWikiUrl = $lines->get($i + 1);

            $airDate = Carbon::createFromFormat('F-d-Y', $lines->get($i + 2));

            if ($show->episodes()->where(['title' => $episodeTitle, 'air_date' => $airDate])->exists()) {
                dd("Duplicate episode found in file: $episodeTitle");
            }

            $show->episodes()->create([
                'title' => $episodeTitle,
                'season' => $seasonNumber,
                'episode_number' => $episodeNumber,
                'air_date' => $airDate,
                'wiki_url' => $episodeWikiUrl,
                'order' => $order++,
            ]);
        }
    }
}
