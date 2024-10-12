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

        for ($i = 0; $i < $lines->count(); $i += 3) {
            if ($lines->get($i) === '') {
                continue;
            }

            [$showCode, $episodeTitle] = explode(' ', $lines->get($i), 2);
            [$seasonAndEpisodeCode, $episodeTitle] = explode(' ', $episodeTitle, 2);
            try {
                [$seasonNumber, $episodeNumber] = explode('x', $seasonAndEpisodeCode, 2);
            } catch (Throwable $e) {
            }

            $episodeTitle = Str::after($episodeTitle, ' ');
            if (preg_match_all('/[\s\w]*:/', $lines->get($i), $matches)) {
                $showPrefix = trim(chop($matches[0][0], ':'));
                $showSuffix = trim(chop($matches[0][1], ':'));

                if ($showPrefix === 'GA') {
                    $showCode = 'GA:BT';
                    $episodeTitle = trim(Str::afterLast($lines->get($i), ':'));
                } else {

                    try {
                        $showPart = Str::after(trim(chop($matches[0][2], ':')), 'Part ');
                    } catch (Throwable $e) {
                        dd($matches);
                    }
                    $episodeTitle = Str::afterLast($lines->get($i), ':');

                    $showCode = match ($showPrefix . ': ' . $showSuffix) {
                        'Seattle Grace: On Call' => 'SG:OC',
                        'Seattle Grace: Message of Hope' => 'SG:MOH',
                        default => dd("Unknown show code: $showPrefix: $showSuffix"),
                    };

                    $seasonNumber = 1;
                    $episodeTitle = $showPart;

                    $episodeTitle = $showPart . ': ' . $episodeTitle;
                }
            }

            $show = Show::firstOrCreate(['code' => $showCode], ['title' => match ($showCode) {
                'GA' => 'Grey\'s Anatomy',
                'PP' => 'Private Practice',
                'S19' => 'Station 19',
                'SG:OC' => 'Seattle Grace: On Call',
                'SG:MOH' => 'Seattle Grace: Message of Hope',
                'GA:BT' => 'Grey\'s Anatomy: B-Team',
                default => dd("Unknown show code: $showCode"),
            }]);

            $episodeWikiUrl = $lines->get($i + 1);

            $airDate = Carbon::createFromFormat('F-d-Y', $lines->get($i + 2));

            if ($show->episodes()->where(['title' => $episodeTitle, 'air_date' => $airDate])->exists()) {
                dd("Duplicate episode found in file");
            }

            $show->episodes()->create([
                'title' => $episodeTitle,
                'season' => $seasonNumber,
                'episode_number' => $episodeNumber,
                'air_date' => $airDate,
                'wiki_url' => $episodeWikiUrl,
            ]);
        }
    }
}
