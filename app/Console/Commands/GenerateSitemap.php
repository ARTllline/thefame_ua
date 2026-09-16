<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the XML sitemap for all public localized pages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sitemap = Sitemap::create();
        $baseUrl = rtrim((string) config('app.url'), '/');

        foreach ($this->localizedUrls($baseUrl, '') as $url) {
            $sitemap->add(
                $this->withAlternates(
                    Url::create($url)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(1.0),
                    $baseUrl,
                    ''
                )
            );
        }

        $servicesCount = 0;

        Service::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->select(['id', 'code', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($services) use ($sitemap, $baseUrl, &$servicesCount): void {
                foreach ($services as $service) {
                    $path = '/services/'.rawurlencode($service->code);

                    foreach ($this->localizedUrls($baseUrl, $path) as $url) {
                        $tag = Url::create($url)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.8);

                        if ($service->updated_at !== null) {
                            $tag->setLastModificationDate($service->updated_at);
                        }

                        $sitemap->add($this->withAlternates($tag, $baseUrl, $path));
                    }

                    $servicesCount++;
                }
            });

        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        $this->info(sprintf(
            'Sitemap generated: %s (%d URLs for %d services).',
            $path,
            4 + ($servicesCount * 4),
            $servicesCount
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function localizedUrls(string $baseUrl, string $path): array
    {
        return [
            $baseUrl.$path,
            $baseUrl.'/ua'.$path,
            $baseUrl.'/ru'.$path,
            $baseUrl.'/en'.$path,
        ];
    }

    private function withAlternates(Url $tag, string $baseUrl, string $path): Url
    {
        return $tag
            ->addAlternate($baseUrl.$path, 'x-default')
            ->addAlternate($baseUrl.'/ua'.$path, 'uk')
            ->addAlternate($baseUrl.'/ru'.$path, 'ru')
            ->addAlternate($baseUrl.'/en'.$path, 'en');
    }
}
