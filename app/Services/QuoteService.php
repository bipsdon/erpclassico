<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a motivational quote for the designer dashboard.
 *
 * Pick strategy (chosen fresh each time the cache expires):
 *   70% chance → pick from the local curated library
 *               (design quotes, Illustrator tips, sublimation tips,
 *                colour theory, jersey design principles, hard-work mindset)
 *   30% chance → fetch from ZenQuotes public API for fresh general motivation
 *               (falls back to local if the API is unavailable)
 *
 * Result cached per-user for 30 minutes so the API is not hammered on
 * every page load, but the quote still rotates throughout the day.
 *
 * ZenQuotes API: https://zenquotes.io/api/random
 * Response format: [{"q":"...", "a":"...", "h":"..."}]
 * Free tier — no API key required. Attribution: zenquotes.io
 */
class QuoteService
{
    private const API_URL     = 'https://zenquotes.io/api/random';
    private const TIMEOUT_S   = 3;    // fail fast — dashboard must not hang
    private const CACHE_TTL_M = 30;   // minutes per user before refreshing
    private const API_WEIGHT  = 30;   // % chance of hitting the API (rest = local)

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    /**
     * Return a quote array: [text, author, tag, source]
     *   source = 'api' | 'local'
     *   tag    = 'design'|'tip'|'jersey'|'colour'|'mindset'|'api'
     */
    public function get(int $userId): array
    {
        $cacheKey = "designer_quote_{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_M), function () {
            // 30% of the time: try the live API for variety
            if (random_int(1, 100) <= self::API_WEIGHT) {
                $apiQuote = $this->fetchFromApi();
                if ($apiQuote !== null) {
                    return $apiQuote;
                }
            }

            // 70% of the time (or on API failure): use the curated local library
            return $this->pickLocal();
        });
    }

    /**
     * Force-bust the cache so the next load fetches a fresh quote.
     */
    public function forget(int $userId): void
    {
        Cache::forget("designer_quote_{$userId}");
    }

    // ──────────────────────────────────────────────
    // ZenQuotes API
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

            $item = $response->json()[0] ?? null;

            if (! $item || empty($item['q']) || empty($item['a'])) {
                return null;
            }

            // Skip the attribution placeholder ZenQuotes injects
            if (stripos((string) $item['a'], 'zenquotes') !== false) {
                return null;
            }

            return [
                'text'   => trim($item['q']),
                'author' => trim($item['a']),
                'tag'    => 'api',
                'source' => 'api',
            ];

        } catch (\Throwable $e) {
            Log::debug('QuoteService: ZenQuotes fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ──────────────────────────────────────────────
    // Local curated library
    // ──────────────────────────────────────────────

    private function pickLocal(): array
    {
        $quote           = $this->localQuotes()[array_rand($this->localQuotes())];
        $quote['source'] = 'local';
        return $quote;
    }

    /** @return array<int, array{text:string, author:string, tag:string}> */
    private function localQuotes(): array
    {
        return [

            // ════════════════════════════════════════
            // HARD WORK & MINDSET
            // ════════════════════════════════════════
            ['tag' => 'mindset',
             'author' => 'Tim Notke',
             'text' => 'Hard work beats talent when talent doesn\'t work hard.'],
            ['tag' => 'mindset',
             'author' => 'Vincent van Gogh',
             'text' => 'Great things are not done by impulse, but by a series of small things brought together.'],
            ['tag' => 'mindset',
             'author' => 'Gary Player',
             'text' => 'The more I practice, the luckier I get.'],
            ['tag' => 'mindset',
             'author' => 'Nelson Mandela',
             'text' => 'It always seems impossible until it\'s done.'],
            ['tag' => 'mindset',
             'author' => 'Sam Levenson',
             'text' => 'Don\'t watch the clock; do what it does — keep going.'],
            ['tag' => 'mindset',
             'author' => 'Mark Twain',
             'text' => 'The secret of getting ahead is getting started.'],
            ['tag' => 'mindset',
             'author' => 'Steve Jobs',
             'text' => 'The only way to do great work is to love what you do.'],
            ['tag' => 'mindset',
             'author' => 'Chris Grosser',
             'text' => 'Opportunities don\'t happen. You create them.'],
            ['tag' => 'mindset',
             'author' => 'Ralph Waldo Emerson',
             'text' => 'Every artist was first an amateur.'],
            ['tag' => 'mindset',
             'author' => 'Ira Glass',
             'text' => 'All of us who do creative work get into it because we have good taste. But there\'s a gap — your taste is good enough to know your early work is a disappointment. Fight through it.'],
            ['tag' => 'mindset',
             'author' => 'Aristotle',
             'text' => 'We are what we repeatedly do. Excellence, then, is not an act but a habit.'],

            // ════════════════════════════════════════
            // GRAPHIC DESIGN
            // ════════════════════════════════════════
            ['tag' => 'design',
             'author' => 'Steve Jobs',
             'text' => 'Design is not just what it looks like and feels like. Design is how it works.'],
            ['tag' => 'design',
             'author' => 'Charles Eames',
             'text' => 'The details are not the details. They make the design.'],
            ['tag' => 'design',
             'author' => 'Saul Bass',
             'text' => 'Design is thinking made visual.'],
            ['tag' => 'design',
             'author' => 'Antoine de Saint-Exupéry',
             'text' => 'Perfection is achieved not when there is nothing more to add, but when there is nothing left to take away.'],
            ['tag' => 'design',
             'author' => 'Leonardo da Vinci',
             'text' => 'Simplicity is the ultimate sophistication.'],
            ['tag' => 'design',
             'author' => 'Joe Sparano',
             'text' => 'Good design is obvious. Great design is transparent.'],
            ['tag' => 'design',
             'author' => 'Alina Wheeler',
             'text' => 'Design is intelligence made visible.'],
            ['tag' => 'design',
             'author' => 'Brian Reed',
             'text' => 'Everything is designed. Few things are designed well.'],
            ['tag' => 'design',
             'author' => 'Massimo Vignelli',
             'text' => 'The life of a designer is a life of fight — fight against the ugliness.'],
            ['tag' => 'design',
             'author' => 'Robert L. Peters',
             'text' => 'Design creates culture. Culture shapes values. Values determine the future.'],
            ['tag' => 'design',
             'author' => 'Lorinda Mamo',
             'text' => 'Every great design begins with an even better story.'],
            ['tag' => 'design',
             'author' => 'Paul Rand',
             'text' => 'Don\'t try to be original. Just try to be good.'],
            ['tag' => 'design',
             'author' => 'Milton Glaser',
             'text' => 'There are three responses to a piece of design — yes, no, and WOW. Wow is the one to aim for.'],
            ['tag' => 'design',
             'author' => 'Stefan Sagmeister',
             'text' => 'The best designers sweat the small stuff — because the small stuff is actually the big stuff.'],

            // ════════════════════════════════════════
            // JERSEY & SUBLIMATION DESIGN
            // ════════════════════════════════════════
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'A team\'s jersey is the flag they carry into every battle. Make it worthy of that honour.'],
            ['tag' => 'jersey',
             'author' => 'Sportswear Design Principle',
             'text' => 'Numbers and names on a jersey tell a story before the whistle even blows.'],
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'The best sports kits feel like a second skin — the design should never get in the way of performance.'],
            ['tag' => 'jersey',
             'author' => 'Sportswear Design Principle',
             'text' => 'Stripes, panels, and piping — the geometry of a jersey tells the world who a team is before they even play.'],
            ['tag' => 'jersey',
             'author' => 'Football Kit Design Principle',
             'text' => 'The crest is the soul of the kit. Everything else serves it.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Design Principle',
             'text' => 'Great sublimation starts before the file is opened — it starts with understanding what the team stands for.'],
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'A well-designed jersey unites eleven individuals into one visual identity.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Always design at 150 DPI minimum at full print size. Upsizing low-res artwork after the fact produces blurry, pixelated prints — no fix in production.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Colours shift when printed on dark polyester. Test your palette on a physical sample before finalising a full run.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Keep important design elements — crests, numbers, sponsor logos — at least 10 mm away from any seam line to avoid distortion during cutting and sewing.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Build your file with separate layers for each panel (front, back, sleeves, collar). It makes revision requests 10× faster to action.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: White in sublimation = the base fabric colour. There is no white ink. If the client wants white, the fabric must be white.'],
            ['tag' => 'jersey',
             'author' => 'Print Production Principle',
             'text' => 'What you see on screen is only as accurate as the colour profile you trust. Always proof on the actual fabric before a full run.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Mirror text and logos on sleeves when the design wraps — what looks right flat will be reversed when worn.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Use spot colour swatches for recurring team colours and name them (e.g. "TeamRed_C10M95Y90K0"). Consistent naming prevents colour drift across orders.'],

            // ════════════════════════════════════════
            // COLOUR THEORY
            // ════════════════════════════════════════
            ['tag' => 'colour',
             'author' => 'Wassily Kandinsky',
             'text' => 'Colour is a power which directly influences the soul.'],
            ['tag' => 'colour',
             'author' => 'Pierre Bonnard',
             'text' => 'Colour does not add a pleasant quality to design — it reinforces it.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => 'Contrast is the engine of visual communication. Without it, everything is noise.'],
            ['tag' => 'colour',
             'author' => 'Coco Chanel',
             'text' => 'The best colour in the whole world is the one that looks good on you.'],
            ['tag' => 'colour',
             'author' => 'Hans Hofmann',
             'text' => 'Colours must fit together as pieces in a puzzle or cogs in a wheel.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => 'White space is not empty space — it is breathing room that makes your colours sing.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => 'Use colour purposefully. Every hue you add must earn its place in the composition.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => 'A complementary colour scheme vibrates with energy. An analogous scheme whispers harmony. Know which one your jersey needs.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: The 60-30-10 rule — 60% dominant colour, 30% secondary, 10% accent. It creates visual balance in any jersey design without overthinking it.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Warm colours advance visually; cool colours recede. Use this to make crests and numbers pop on a jersey from 30 metres away.'],
            ['tag' => 'colour',
             'author' => 'Josef Albers',
             'text' => 'In visual perception a colour is almost never seen as it really is — as it physically is. This fact makes colour the most relative medium in art.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: CMYK black (C0 M0 Y0 K100) looks washed out compared to rich black (C60 M40 Y40 K100). Use rich black for large dark panels on jerseys.'],

            // ════════════════════════════════════════
            // ADOBE ILLUSTRATOR TIPS & HACKS
            // ════════════════════════════════════════
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Hold Alt and drag any object to duplicate it instantly. Much faster than Ctrl+C → Ctrl+V when you\'re building repeated jersey elements.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+D repeats your last transform (move, rotate, scale). Build a row of player numbers by moving one, then hammering Ctrl+D for the rest.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Lock your template/mockup layer (Ctrl+2) before designing. Accidental moves waste time — lock it and forget it.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Use Global Swatches for team colours. Double-click a global swatch, change the value, and every single object using it updates across the entire file instantly.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 "Align to Artboard" in the Align panel (shift any option from selection to artboard) centres elements perfectly — no more nudging crests by eye.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Y toggles Outline mode. Use it to hunt for stray anchor points or hidden overlapping paths before sending files to production.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Ctrl+Shift+O converts all text to outlines. Do this on a duplicate before sending to the printer — eliminates every "font missing" issue permanently.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 The Eyedropper (I) can copy a full appearance — fill, stroke, effects — from one object to another. Hold Alt when clicking to apply instead of sample.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Clipping Masks (Ctrl+7) let you drop any pattern or texture inside a jersey panel shape without permanently cropping the artwork. Edit the mask to reposition freely.'],
            ['tag' => 'tip',
             'author' => 'File Hygiene',
             'text' => '💡 Name every layer clearly: Front_Panel, Back_Panel, Left_Sleeve, Numbers, Crest, Template. The 30 seconds it takes saves 30 minutes when a client requests a revision a month later.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 The Width Tool (Shift+W) lets you vary stroke thickness at any point along a path — great for dynamic swooshes, speed lines, and sleeve accents.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Pathfinder (Shift+Ctrl+F9) → Unite merges selected shapes into one clean path. Use it to combine jersey panel outlines instead of manually tracing edges.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Set document colour mode to CMYK (File → Document Color Mode) for any jersey file going to print. RGB values look great on screen — they don\'t always survive the press.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Use the Appearance panel (Shift+F6) to stack multiple fills and strokes on a single object. Layer a texture fill over a flat colour fill instead of duplicating shapes.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Shift+[ sends an object to the bottom of the layer stack. Ctrl+Shift+] brings it to the top. Faster than dragging layers in the Layers panel for quick reordering.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Recolour Artwork (Edit → Recolor Artwork) lets you swap every colour in a design at once — perfect for producing alternate colourways of the same jersey kit without duplicating files.'],
            ['tag' => 'tip',
             'author' => 'Production Workflow',
             'text' => '💡 Save every major version with a date-and-version suffix: Kit_FC_United_v3_2026-10-11. Clients always ask for "the version from last week" — make sure you can find it.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Object → Expand Appearance before exporting to flatten any live effects (drop shadows, glows) into clean vector paths. Prevents rendering surprises in the RIP software.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Hold Shift while scaling to constrain proportions. Hold Shift+Alt to scale from the centre. Both work on groups — no need to ungroup first.'],

            // ════════════════════════════════════════
            // HARD WORK & MINDSET — additional
            // ════════════════════════════════════════
            ['tag' => 'mindset',
             'author' => 'Thomas Edison',
             'text' => 'Genius is one percent inspiration and ninety-nine percent perspiration.'],
            ['tag' => 'mindset',
             'author' => 'Winston Churchill',
             'text' => 'Success is not final, failure is not fatal — it is the courage to continue that counts.'],
            ['tag' => 'mindset',
             'author' => 'Pablo Picasso',
             'text' => 'Inspiration exists, but it has to find you working.'],
            ['tag' => 'mindset',
             'author' => 'Bruce Lee',
             'text' => 'I fear not the man who has practised 10,000 kicks once. I fear the man who has practised one kick 10,000 times.'],
            ['tag' => 'mindset',
             'author' => 'Michelangelo',
             'text' => 'If people knew how hard I worked to get my mastery, it wouldn\'t seem so wonderful at all.'],
            ['tag' => 'mindset',
             'author' => 'James Clear',
             'text' => 'Every action you take is a vote for the type of person you wish to become.'],
            ['tag' => 'mindset',
             'author' => 'Kobe Bryant',
             'text' => 'Everything negative — pressure, challenges — is all an opportunity for me to rise.'],
            ['tag' => 'mindset',
             'author' => 'Epictetus',
             'text' => 'It\'s not what happens to you, but how you react to it that matters.'],
            ['tag' => 'mindset',
             'author' => 'Maya Angelou',
             'text' => 'You can\'t use up creativity. The more you use, the more you have.'],
            ['tag' => 'mindset',
             'author' => 'Jony Ive',
             'text' => 'Real fearlessness is the product of tenderness. You can be fearless only if you are willing to care.'],
            ['tag' => 'mindset',
             'author' => 'Seth Godin',
             'text' => 'Doing the best at this moment puts you in the best place for the next moment.'],
            ['tag' => 'mindset',
             'author' => 'Austin Kleon',
             'text' => 'The best thing you can do is to share what you know and let others build on it.'],
            ['tag' => 'mindset',
             'author' => 'David Ogilvy',
             'text' => 'The best ideas come as jokes. Make your thinking as funny as possible.'],
            ['tag' => 'mindset',
             'author' => 'Confucius',
             'text' => 'He who learns but does not think is lost. He who thinks but does not learn is in great danger.'],
            ['tag' => 'mindset',
             'author' => 'Frank Lloyd Wright',
             'text' => 'I know the price of success: dedication, hard work, and an unremitting devotion to the things you want to see happen.'],

            // ════════════════════════════════════════
            // GRAPHIC DESIGN — additional
            // ════════════════════════════════════════
            ['tag' => 'design',
             'author' => 'Paul Rand',
             'text' => 'Design is the silent ambassador of your brand.'],
            ['tag' => 'design',
             'author' => 'David Carson',
             'text' => 'Don\'t mistake legibility for communication.'],
            ['tag' => 'design',
             'author' => 'Tibor Kalman',
             'text' => 'We\'re not here to help clients erase the world or make it look pretty. We\'re here to make it a better place.'],
            ['tag' => 'design',
             'author' => 'Dieter Rams',
             'text' => 'Good design is as little design as possible.'],
            ['tag' => 'design',
             'author' => 'Dieter Rams',
             'text' => 'Good design is honest. It does not make a product more innovative, powerful or valuable than it really is.'],
            ['tag' => 'design',
             'author' => 'Erik Spiekermann',
             'text' => 'Designing is not a profession but an attitude.'],
            ['tag' => 'design',
             'author' => 'Chip Kidd',
             'text' => 'A great book cover is not the illustration of content — it is the translation of it.'],
            ['tag' => 'design',
             'author' => 'Michael Bierut',
             'text' => 'The difference between good design and great design is intelligence applied.'],
            ['tag' => 'design',
             'author' => 'Ivan Chermayeff',
             'text' => 'Design is directed toward human beings. To design is to solve human problems.'],
            ['tag' => 'design',
             'author' => 'Neville Brody',
             'text' => 'The screen is not the book. The book is not the poster. Each medium has its own grammar.'],
            ['tag' => 'design',
             'author' => 'Jessica Hische',
             'text' => 'The work you do while you procrastinate is probably the work you should be doing for the rest of your life.'],
            ['tag' => 'design',
             'author' => 'Coco Chanel',
             'text' => 'Simplicity is the keynote of all true elegance.'],
            ['tag' => 'design',
             'author' => 'Hartmut Esslinger',
             'text' => 'A design that doesn\'t challenge anything isn\'t worth designing.'],
            ['tag' => 'design',
             'author' => 'Viktor Papanek',
             'text' => 'The only important thing about design is how it relates to people.'],
            ['tag' => 'design',
             'author' => 'Alan Fletcher',
             'text' => 'The art of communication is the language of leadership.'],
            ['tag' => 'design',
             'author' => 'Ed Catmull',
             'text' => 'Early on, all our movies suck. The goal is to fix them — iteration is the engine of creativity.'],

            // ════════════════════════════════════════
            // JERSEY & SUBLIMATION DESIGN — additional
            // ════════════════════════════════════════
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'Typography on a jersey is not decoration — it\'s identity. Choose a number font that reads instantly from the stands.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Bleed your artwork at least 5 mm beyond every cut line. Anything less and you risk a white edge on the finished garment.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Gradients work beautifully in sublimation — but test banding on long gradient runs. Some RIP profiles posterise subtle fades.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Avoid thin strokes below 1pt on player names and numbers. They may disappear entirely or look ragged on fabric at print resolution.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Complex drop shadows and outer glows add production time and can look muddy on fabric. A hard offset shadow or a clean outline often reads better.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Get the client\'s official Pantone codes before starting. Guessing a team colour from a photo costs you at least one reprint.'],
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'A kit worn in victory is remembered. A kit worn in defeat still needs to look great — design for both.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Keep a colour conversion reference sheet (Pantone → CMYK → sRGB) for every regular client. Consistent colour across orders builds trust.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Ink coverage above 280% total area ink (TAC) on polyester can cause bleeding or transfer marks. Keep saturated dark areas within safe limits.'],
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'Sponsor logos are part of the design, not a burden on it. Give them space and let them breathe — a happy sponsor is a repeat order.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: When repeating a pattern across panels, offset it slightly at seams. A perfectly aligned seam join is nearly impossible in production — plan for the mismatch.'],
            ['tag' => 'jersey',
             'author' => 'Sportswear Design Principle',
             'text' => 'The away kit is as important as the home kit. It is the design the opposition and away fans see most.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Number and name fonts should be minimum 50mm tall on the back of a jersey for readability from a referee\'s distance.'],
            ['tag' => 'jersey',
             'author' => 'Kit Design Principle',
             'text' => 'Three colours maximum for a clean kit. Every colour you add is another thing that can clash.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Save a flat-colour version alongside every gradient design. If a client ever needs a screen-print version, you\'ll be glad it exists.'],
            ['tag' => 'jersey',
             'author' => 'Sublimation Tip',
             'text' => '🎽 Sublimation tip: Polyester dries out dye colour by 10–15% compared to screen. Always adjust saturation upward in your proof if you want punchy output on fabric.'],

            // ════════════════════════════════════════
            // COLOUR THEORY — additional
            // ════════════════════════════════════════
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Triadic colour schemes — three colours equally spaced on the colour wheel — feel energetic and bold. Perfect for loud, aggressive kit designs.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Desaturate your design and check it in greyscale. If the layout loses all meaning, your composition is relying on colour instead of value — fix the values first.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Never use pure #000000 black for body text on screen. A very dark grey (#1a1a1a or similar) reads softer and reduces eye fatigue over long viewing sessions.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: The simultaneous contrast effect makes the same grey look lighter or darker depending on its surrounding colour. Check your mid-tones against their actual background.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Split-complementary schemes — a base colour plus two colours adjacent to its complement — give contrast without the tension of a true complement pairing.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Tints (colour + white) and shades (colour + black) of the same hue create depth and hierarchy without introducing additional colours to the palette.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Optical mixing — placing small dots of two colours next to each other — creates a third perceived colour from a distance. Pointillist technique, but it applies to sublimation halftone patterns too.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Colour temperature changes perceived weight. Cool colours feel lighter; warm colours feel heavier. Use this to balance a busy jersey design visually.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: For WCAG AA accessibility, text needs a contrast ratio of at least 4.5:1 against its background. Player numbers need to be legible under floodlights — aim higher.'],
            ['tag' => 'colour',
             'author' => 'Johannes Itten',
             'text' => 'Colour is life; a world without colour appears dead. As a flame produces light, light produces colour.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Fluorescent or neon colours exist outside the printable CMYK gamut. If a client requests neon, manage expectations early — or plan for a speciality ink.'],
            ['tag' => 'colour',
             'author' => 'Colour Theory Principle',
             'text' => '🎨 Colour tip: Earthy, muted tones feel premium and timeless on a jersey. Saturated, vivid tones feel energetic and youthful. Match the palette to the club\'s identity, not just their preferences.'],
            ['tag' => 'colour',
             'author' => 'Faber Birren',
             'text' => 'Colour and light are the two basic elements which, together, produce all visual experience.'],

            // ════════════════════════════════════════
            // ADOBE ILLUSTRATOR TIPS — additional
            // ════════════════════════════════════════
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+G groups selected objects. Ctrl+Shift+G ungroups them. Get into the habit of grouping related elements (e.g. all crest components) immediately — it prevents selection chaos later.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Press R to activate the Rotate tool, then click to set a custom pivot point before rotating. Essential for symmetrical jersey panel rotations around a seam point.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Double-clicking into a group enters isolation mode — you can edit individual objects without ungrouping. Press Escape to exit back to the parent level.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Object → Pattern → Make turns any selected artwork into a live repeating pattern swatch. Drag it onto fabric panels for instant seamless sublimation textures.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 The Blend Tool (W) creates a smooth series of shapes or colours between two objects. Use it for gradient number outlines or morphing stripe widths across a panel.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Alt+B creates a blend between two selected objects with the last-used blend options. Faster than going through the menu every time.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Image Trace (Object → Image Trace → Expand) converts a raster logo into clean vector paths. Use "High Fidelity Photo" or "16 Colours" preset as your starting point, then clean up anchor points manually.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Alt+Shift+K opens the Character Styles panel. Define a style for player name text and another for numbers — re-applying consistent formatting takes one click instead of six.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Type → Create Outlines (Ctrl+Shift+O) on a copy of your text layer — never on the original. Keep the live text layer hidden so you can re-edit if a name is misspelled.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 The Scissors tool (C) cuts a path at any anchor point or segment. Use it to split jersey panel outlines into separate editable pieces without Pathfinder.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+F pastes in front; Ctrl+B pastes behind. Both paste exactly in place relative to the original — indispensable when building layered jersey shadow effects.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 File → Package collects all linked files and fonts into one folder. Send the packaged folder to another designer or archive it with the final file — no more missing assets.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Use artboards (Shift+O) to keep all jersey views — front, back, left sleeve, right sleeve — in a single file. Export as multi-page PDF or individual PNGs per artboard.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Alt+click the eyeball icon in the Layers panel to solo that layer — hides everything else instantly. Great for checking isolated panels without deleting visibility toggles manually.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Effect → Distort & Transform → Transform applies move/rotate/scale multiple times as a live effect — perfect for creating radiating stripe patterns without duplicating objects.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Symbols (Window → Symbols) let you place the same crest or logo repeatedly across multiple artboards. Edit the symbol once and all instances update — huge time saver on kit sets.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Always flatten your Transparency (Object → Flatten Transparency) on a duplicate before sending to print. Live transparency can cause unexpected results in some RIP workflows.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Shift+H hides/shows artboard edges. When you\'re finalising a jersey layout, hide the edges to see how the design looks as a clean visual without distracting borders.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 The Variable Width Profile dropdown in the Stroke panel applies pre-built tapering profiles to any stroke in one click — no Width Tool manipulation required for common effects.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Ctrl+Alt+2 unlocks all locked objects in the active layer. Useful when you\'ve locked a template layer and need to temporarily adjust it without hunting through the Layers panel.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Effect → Stylize → Round Corners adds rounded corners to any sharp-edged shape as a live effect — adjustable at any time without redrawing. Great for modern jersey number styles.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Window → Workspace → Save Workspace. Set up your ideal panel layout for jersey work (Layers, Swatches, Appearance, Align) and save it. Load it on any machine or after an Illustrator reset.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Shortcut',
             'text' => '💡 Select an object and press the Tab key to cycle selection to the next object in the stacking order. Faster than clicking in a dense, overlapping design.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Feature',
             'text' => '💡 Envelope Distort (Object → Envelope Distort → Make with Warp) lets you wrap flat text or graphics around curved jersey shapes for realistic mockup previews.'],
            ['tag' => 'tip',
             'author' => 'Illustrator Workflow',
             'text' => '💡 Before client delivery, run Document Setup → check "View" for overprinting. Accidental overprint settings on black objects cause colour knockout failures — catch it before the printer does.'],

        ];
    }
}

