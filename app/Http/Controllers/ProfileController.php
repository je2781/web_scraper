<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;


class ProfileController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'error' => 'Search query is required.'
            ], 400);
        }
        try {


            $targetUrl = $this->buildUrlFromUsername($query);


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
                        'likes' => $data['likes'] ?? 0,
                    ]
                );

            }

            return response()->json([
                'query' => $query,
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Scraper error: ' . $e->getMessage());
        }

        // Scout full-text search
        // $results = Profile::search($query)->get();

        // return response()->json([
        //     'query' => $query,
        //     'results' => $results,
        // ]);
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
                $socialNodes = $lastChildNode->filter('a');

                $socialNodes->each(function (Crawler $node) use (&$mediaLinks) {
                    $href = $node->attr('href');

                    switch ($node->attr('data-type')) {
                        case 'instagram':
                            if ($href)
                                $mediaLinks['instagram'][] = $href;
                            break;

                        case 'twitter':
                            if ($href)
                                $mediaLinks['twitter'][] = $href;
                            break;

                        default:
                            if ($href)
                                $mediaLinks['other'][] = $href;
                            break;
                    }
                });
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


            try {
                // Select the div that contains the heart icon
                $visitingProfileUrl = $profileNode->attr('data-clickurl');
                if ($visitingProfileUrl) {

                    $visitingProfileResponse = Http::get($visitingProfileUrl);

                    if ($visitingProfileResponse->getStatusCode() !== 200) {
                        throw new \Exception("Failed to fetch {$visitingProfileUrl}: {$visitingProfileResponse->getStatusCode()}");
                    }

                    $html = (string) $visitingProfileResponse->getBody();

                    $crawler = new Crawler($html);

                    $likesNode = $crawler->filter('.b-profile__sections__link.m-likes > .b-profile__sections__count')->first();

                    if ($likesNode->count()) {
                        $likesText = trim($likesNode->text()); // e.g. "45.6k" or "10.4m"

                        $lastChar = strtolower(substr($likesText, -1));
                        $number = (float) $likesText;

                        switch ($lastChar) {
                            case 'k':
                                $likes = (int) ($number * 1000);
                                break;
                            case 'm':
                                $likes = (int) ($number * 1000000);
                                break;
                            default:
                                $likes = (int) $number;
                        }
                    } else {
                        $likes = 0;
                    }
                }



            } catch (\Exception $e) {
                $likes = 0;
            }
            // Push profile into results
            $profiles[] = [
                'name' => $name,
                'bio' => $bio,
                'metadata' => $mediaLinks,
                'username' => $profileNode->attr('data-username'),
                'sources' => [$url],
                'likes' => $likes,
            ];
        });

        return $profiles;
    }
}
