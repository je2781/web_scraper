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

use Psr\Http\Message\ResponseInterface;

class ScrapeProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $username;
    public ?string $url;
    public int $tries = 3;


    /**
     * Create a new job instance.
     */
    public function __construct(string $username, ?string $url = null)
    {
        $this->username = $username;
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // polite defaults
        $client = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'MyScraperBot/1.0 (+https://yourdomain.com/bot; contact@yourdomain.com)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        // Resolve URL if not passed (caller should provide a canonical URL)
        $targetUrl = $this->url ?? $this->buildUrlFromUsername($this->username);

        $response = $client->get($targetUrl);

        if ($response->getStatusCode() !== 200) {
            // handle non-200 gracefully or throw to retry
            return;
        }

        $html = (string) $response->getBody();
        $data = $this->parseProfileHtml($html, $targetUrl);

        // Upsert profile record
        $profile = Profile::updateOrCreate(
            ['username' => $this->username],
            [
                'display_name' => $data['display_name'] ?? null,
                'name' => $data['name'],
                'bio' => $data['bio'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'sources' => $data['sources'] ?? [$targetUrl],
                'likes' => $data['likes'] ?? 0,
            ]
        );

    }

    protected function buildUrlFromUsername(string $username): string
    {
        return "https://onlyfans.com/{$username}";
    }

    protected function parseProfileHtml(string $html, string $url): array
    {
        $crawler = new Crawler($html, $url);

        // NOTE: Do not assume selectors — adapt for the site you have permission to parse.
        $displayName = null;
        try {
            $title = trim($crawler->filter('title')->text());
            $displayName = $title;
        } catch (\Exception $e) {
            $displayName = null;
        }

        $name = null;
        try {
            $nameNode = $crawler->filter('.g-user-name')->first();
            $name = $nameNode->count() ? trim($nameNode->text()) : null;
        } catch (\Exception $e) {
            $name = null;
        }

        $username = null;
        try {
            $usernameNode = $crawler->filter('.g-user-username')->first();
            $username = $usernameNode->count() ? explode('@', trim($usernameNode->text()))[1] : null;
        } catch (\Exception $e) {
            $username = null;
        }

        $bio = null;
        try {
            $bioNode = $crawler->filter('.b-user-info__text')->first();
            $bio = $bioNode->count() ? trim($bioNode->text()) : null;
        } catch (\Exception $e) {
            $bio = null;
        }

        // Example numeric extraction
        $likes = 0;
        try {
            $likesNode = $crawler->filter('.b-profile__sections__link.m-likes > .b-profile__sections__count')->first();
            $likes = $likesNode->count() ? (int) $likesNode->text() : 0;
        } catch (\Exception $e) {
            $likes = 0;
        }

        $metadata = [
            'raw_title' => $displayName,
            // add more data you parse
        ];

        return [
            'display_name' => $displayName,
            'username' => $username,
            'name' => $name,
            'bio' => $bio,
            'metadata' => $metadata,
            'sources' => [$url],
            'likes' => $likes,
        ];
    }
}
