<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Profile;
use GuzzleHttp\Client;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapeProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public int $tries = 3;


    /**
     * Create a new job instance.
     */
    public function __construct(public $username) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {


            $targetUrl = $this->buildUrlFromUsername($this->username);

            $res = Http::get($targetUrl);


            if ($res->getStatusCode() !== 200) {
                throw new \Exception("Failed to fetch {$targetUrl}: {$res->getStatusCode()}");
            }

            $html = (string) $res->getBody();


            Log::info(substr($html, 0, 500));
            $dataList = $this->parseProfileHtml($html, $targetUrl);

            foreach ($dataList as $data) {
                Profile::updateOrCreate(
                    ['username' => $data['username']],
                    [
                        'name' => $data['name'],
                        'bio' => $data['bio'] ?? null,
                        'metadata' => $data['metadata'] ?? null,
                        'sources' => $data['sources'] ?? [$targetUrl],
                        'likes' => $data['likes'],
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Scraper error: ' . $e->getMessage());
        }
    }


     protected function buildUrlFromUsername(string $username): string
    {
        return "https://onlyfinder.co/{$username}";
    }

    protected function parseProfileHtml(string $html, string $url): array
    {
        $crawler = new Crawler($html, $url);

        $profiles = [];

        $crawler->filter('.user-profile.profile-container')->each(function (Crawler $profileNode) use (&$profiles, $url) {
            $lastChildNode = $profileNode->children()->last();

            // --- Media Links ---
            $mediaLinks = [];
            try {
                $mediaLinks['instagram'][] = $lastChildNode->filter('a[data-type="instagram"]')->first()->attr('href');
                $mediaLinks['twitter'][] = $lastChildNode->filter('a[data-type="twitter"]')->first()->attr('href');
                $mediaLinks['tiktok'][] = $lastChildNode->filter('a[data-type="tiktok"]')->first()->attr('href');
            } catch (\Exception $e) {
                $mediaLinks = [];
            }

            // --- Name ---
            $name = null;
            try {
                $nameNode = $lastChildNode->filter('a h3')->first();
                $name = $nameNode->count() ? trim($nameNode->text()) : null;
            } catch (\Exception $e) {
                $name = null;
            }

            // --- Bio ---
            $bio = null;
            try {
                $bioNode = $lastChildNode->filter('.about-profile p')->first();
                $bio = $bioNode->count() ? trim($bioNode->text()) : null;
            } catch (\Exception $e) {
                $bio = null;
            }

            // --- Likes ---

            $likes = 0;


            // Extract likes
            try {
                $imgNode = $profileNode->filter('img[alt="Favorite count icon"]')->first();
                $sibling= $imgNode->getNode(0)->nextSibling;   // get the next sibling

                $likes = $sibling ? (int) trim(str_replace(',', '', $sibling->textContent)) : 0;
            } catch (\Exception $e) {
                $likes = 0;
            }
            // Push profile into results
            $profiles[] = [
                'name' => $name,
                'bio' => $bio,
                'metadata' => $mediaLinks,
                'username' => $profileNode->attr('data-username'),
                'sources' => [$profileNode->attr('data-clickurl')],
                'likes' => $likes,
            ];
        });


        return $profiles;
    }
}
