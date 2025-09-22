<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Spatie\Browsershot\Browsershot;


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


        // Scout full-text search
        $results = Profile::search($query)->get();

        return response()->json([
            'query' => $query,
            'results' => $results,
        ]);
    }


}
