<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a motivational quote for the designer dashboard.
 *
 * Strategy:
 *   1. Try the ZenQuotes public API (https://zenquotes.io/api/random).
 *      - Result is cached per-user for 30 minutes so the API is not
 *        hit on every page load, but the quote still rotates during the day.
 *   2. On any failure (timeout, bad response, no network) fall back to
 *      the built-in local quote list so the UI never breaks.
 *
 * API response format: [{"q":"...", "a":"...", "h":"..."}]
 */
class QuoteService
{
    private const API_URL     = 'https://zenquotes.io/api/random';
    private const TIMEOUT_S   = 3;       // fail fast — dashboard must not hang
    private const CACHE_TTL_M = 30;      // minutes before fetching a new quote

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    /**
     * Return a quote array with keys: text, author, tag, source.
     * source = 'api' | 'local'
     */
    public function get(int $userId): array
    {
        $cacheKey = "designer_quote_{$userId}";

        // Each user gets their own cache slot so teammates don't all see
        // the same quote at the same time.
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_M), function () {
            return $this->fetchFromApi() ?? $this->localFallback();
        });
    }

    /**
     * Force-bust the cache for a user (useful for testing or manual refresh).
     */
    public function forget(int $userId): void
    {
        Cache::forget("designer_quote_{$userId}");
    }

    // ──────────────────────────────────────────────
    // API fetch
    // ──────────────────────────────────────────────

    private function fetchFromApi(): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_S)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::API_URL);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            // ZenQuotes returns an array; the first element is the quote.
            $item = $data[0] ?? null;

            if (! $item || empty($item['q']) || empty($item['a'])) {
                return null;
            }

            // Skip ZenQuotes attribution placeholder quotes
            if (str_contains($item['a'], 'zenquotes')) {
                return null;
            }

            return [
                'text'   => trim($item['q']),
                'author' => trim($item['a']),
                'tag'    => 'api',
                'source' => 'api',
            ];

        } catch (\Throwable $e) {
            // Log silently — don't expose API errors to the user
            Log::debug('QuoteService: API fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ──────────────────────────────────────────────
    // Local fallback library
    // ──────────────────────────────────────────────

    private function localFallback(): array
    {
        $quote              = $this->randomLocalQuote();
        $quote['source']    = 'local';
        return $quote;
    }

    private function randomLocalQuote(): array
    {
        $quotes = [

            // ── Hard work & mindset ───────────────────────────────
            ['text' => 'Hard work beats talent when talent doesn\'t work hard.',
             'author' => 'Tim Notke', 'tag' => 'mindset'],
            ['text' => 'Great things are not done by impulse, but by a series of small things brought together.',
             'author' => 'Vincent van Gogh', 'tag' => 'mindset'],
            ['text' => 'The more I practice, the luckier I get.',
             'author' => 'Gary Player', 'tag' => 'mindset'],
            ['text' => 'It always seems impossible until it\'s done.',
             'author' => 'Nelson Mandela', 'tag' => 'mindset'],
            ['text' => 'Don\'t watch the clock; do what it does — keep going.',
             'author' => 'Sam Levenson', 'tag' => 'mindset'],
            ['text' => 'The secret of getting ahead is getting started.',
             'author' => 'Mark Twain', 'tag' => 'mindset'],
            ['text' => 'The only way to do great work is to love what you do.',
             'author' => 'Steve Jobs', 'tag' => 'mindset'],
            ['text' => 'Opportunities don\'t happen. You create them.',
             'author' => 'Chris Grosser', 'tag' => 'mindset'],
            ['text' => 'Every artist was first an amateur.',
             'author' => 'Ralph Waldo Emerson', 'tag' => 'mindset'],

            // ── Graphic design ────────────────────────────────────
            ['text' => 'Design is not just what it looks like and feels like. Design is how it works.',
             'author' => 'Steve Jobs', 'tag' => 'design'],
            ['text' => 'The details are not the details. They make the design.',
             'author' => 'Charles Eames', 'tag' => 'design'],
            ['text' => 'Design is thinking made visual.',
             'author' => 'Saul Bass', 'tag' => 'design'],
            ['text' => 'A designer knows he has achieved perfection not when there is nothing left to add, but when there is nothing left to take away.',
             'author' => 'Antoine de Saint-Exupéry', 'tag' => 'design'],
            ['text' => 'Simplicity is the ultimate sophistication.',
             'author' => 'Leonardo da Vinci', 'tag' => 'design'],
            ['text' => 'Good design is obvious. Great design is transparent.',
             'author' => 'Joe Sparano', 'tag' => 'design'],
            ['text' => 'Design is intelligence made visible.',
             'author' => 'Alina Wheeler', 'tag' => 'design'],
            ['text' => 'Everything is designed. Few things are designed well.',
             'author' => 'Brian Reed', 'tag' => 'design'],
            ['text' => 'The life of a designer is a life of fight — fight against the ugliness.',
             'author' => 'Massimo Vignelli', 'tag' => 'design'],
            ['text' => 'Design creates culture. Culture shapes values. Values determine the future.',
             'author' => 'Robert L. Peters', 'tag' => 'design'],
            ['text' => 'Make it simple, but significant.',
             'author' => 'Don Draper', 'tag' => 'design'],
            ['text' => 'Every great design begins with an even better story.',
             'author' => 'Lorinda Mamo', 'tag' => 'design'],

            // ── Jersey & sportswear design ────────────────────────
            ['text' => 'A team\'s jersey is the flag they carry into every battle. Make it worthy of that honour.',
             'author' => 'Kit Design Principle', 'tag' => 'jersey'],
            ['text' => 'Numbers and names on a jersey tell a story before the whistle even blows.',
             'author' => 'Sportswear Design Principle', 'tag' => 'jersey'],
            ['text' => 'The best sports kits feel like a second skin — the design should never get in the way of performance.',
             'author' => 'Kit Design Principle', 'tag' => 'jersey'],
            ['text' => 'Stripes, panels, and piping — the geometry of a jersey tells the world who a team is before they even play.',
             'author' => 'Sportswear Design Principle', 'tag' => 'jersey'],
            ['text' => 'The crest is the soul of the kit. Everything else serves it.',
             'author' => 'Football Kit Design Principle', 'tag' => 'jersey'],
            ['text' => 'Great sublimation starts before the file is opened — it starts with understanding what the team stands for.',
             'author' => 'Sublimation Design Principle', 'tag' => 'jersey'],
            ['text' => 'A well-designed jersey unites eleven individuals into one identity.',
             'author' => 'Kit Design Principle', 'tag' => 'jersey'],
            ['text' => 'In sublimation printing, what you see on screen is only as accurate as the colour profile you trust. Always proof.',
             'author' => 'Print Production Principle', 'tag' => 'jersey'],

            // ── Colour theory ─────────────────────────────────────
            ['text' => 'Colour is a power which directly influences the soul.',
             'author' => 'Wassily Kandinsky', 'tag' => 'colour'],
            ['text' => 'Colour does not add a pleasant quality to design — it reinforces it.',
             'author' => 'Pierre Bonnard', 'tag' => 'colour'],
            ['text' => 'Contrast is the engine of visual communication. Without it, everything is noise.',
             'author' => 'Colour Theory Principle', 'tag' => 'colour'],
            ['text' => 'The best colour in the whole world is the one that looks good on you.',
             'author' => 'Coco Chanel', 'tag' => 'colour'],
            ['text' => 'Colours must fit together as pieces in a puzzle or cogs in a wheel.',
             'author' => 'Hans Hofmann', 'tag' => 'colour'],
            ['text' => 'White space is not empty space — it is breathing room that makes your colours sing.',
             'author' => 'Design Principle', 'tag' => 'colour'],
            ['text' => 'Use colour purposefully. Every hue you add should earn its place.',
             'author' => 'Colour Theory Principle', 'tag' => 'colour'],
            ['text' => 'To design with colour is to create emotion before the eye reads a single word.',
             'author' => 'Colour Theory Principle', 'tag' => 'colour'],
            ['text' => 'A complementary colour scheme vibrates with energy. An analogous scheme whispers harmony. Know which one your jersey needs.',
             'author' => 'Colour Theory Principle', 'tag' => 'colour'],

            // ── Illustrator tips & hacks ──────────────────────────
            ['text' => '💡 Tip: Hold Alt and drag any object to instantly duplicate it in place — faster than copy-paste every time.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Ctrl+D repeats your last transform. Use it to chain pattern elements on a jersey without typing coordinates twice.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Lock your template layer before designing. It prevents accidental moves and keeps your artboard clean.',
             'author' => 'Illustrator Workflow', 'tag' => 'tip'],
            ['text' => '💡 Tip: Use Global Swatches for team colours. Change one swatch and every element updates instantly — a lifesaver on revision rounds.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: "Align to Artboard" in the Align panel centres objects perfectly every time — no more eyeballing crest placement.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Outline mode (Ctrl+Y) reveals stray anchor points and hidden paths that look fine on screen but ruin a print.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Convert fonts to outlines (Ctrl+Shift+O) before sending files for production — zero font substitution issues, guaranteed.',
             'author' => 'Production Best Practice', 'tag' => 'tip'],
            ['text' => '💡 Tip: The Eyedropper (I) picks more than colour — hold Alt to apply a full appearance style from one object to another.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Use Clipping Masks (Ctrl+7) to fit sublimation patterns inside jersey panels cleanly without destroying the original artwork.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Name every layer. Future you — and anyone else opening the file — will thank present you enormously.',
             'author' => 'File Hygiene Principle', 'tag' => 'tip'],
            ['text' => '💡 Tip: The Width Tool (Shift+W) varies stroke thickness along any path — perfect for dynamic swooshes and sleeve details.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Save every version with a date suffix. Clients always come back asking for "the first version you showed us".',
             'author' => 'Production Best Practice', 'tag' => 'tip'],
            ['text' => '💡 Tip: Use the Pathfinder panel (Shift+Ctrl+F9) to combine shapes cleanly instead of manually drawing every jersey panel edge.',
             'author' => 'Illustrator Hack', 'tag' => 'tip'],
            ['text' => '💡 Tip: Set your document colour mode to CMYK if the file is going to print — RGB numbers look great on screen, not always in ink.',
             'author' => 'Print Production Principle', 'tag' => 'tip'],
        ];

        return $quotes[array_rand($quotes)];
    }
}
