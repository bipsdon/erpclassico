<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\HasPerfStats;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignerController extends Controller
{
    use HasPerfStats;

    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan  = $this->scheduler->buildDailyPlan();
        $queue = $plan->designQueue;

        $upcomingOrders = Order::with([])
            ->whereNotIn('status', ['cancelled'])
            ->whereNotIn('stage', ['delivered'])
            ->whereBetween('delivery_date', [
                now()->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->orderBy('delivery_date')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->limit(20)
            ->get();

        $quote = $this->randomQuote();

        return view('dashboard.designer', compact('queue', 'upcomingOrders', 'quote'));
    }

    // ──────────────────────────────────────────────
    // Motivational quotes for designers
    // ──────────────────────────────────────────────

    private function randomQuote(): array
    {
        $quotes = [
            [
                'text'   => 'Design is not just what it looks like and feels like. Design is how it works.',
                'author' => 'Steve Jobs',
            ],
            [
                'text'   => 'Creativity is intelligence having fun.',
                'author' => 'Albert Einstein',
            ],
            [
                'text'   => 'Every great design begins with an even better story.',
                'author' => 'Lorinda Mamo',
            ],
            [
                'text'   => 'Good design is obvious. Great design is transparent.',
                'author' => 'Joe Sparano',
            ],
            [
                'text'   => 'The details are not the details. They make the design.',
                'author' => 'Charles Eames',
            ],
            [
                'text'   => 'Design is thinking made visual.',
                'author' => 'Saul Bass',
            ],
            [
                'text'   => 'Simplicity is the ultimate sophistication.',
                'author' => 'Leonardo da Vinci',
            ],
            [
                'text'   => 'The best designs are those you never notice — they just feel right.',
                'author' => 'Jonathan Ive',
            ],
            [
                'text'   => 'You can\'t use up creativity. The more you use, the more you have.',
                'author' => 'Maya Angelou',
            ],
            [
                'text'   => 'Logic will get you from A to B. Imagination will take you everywhere.',
                'author' => 'Albert Einstein',
            ],
            [
                'text'   => 'A designer knows he has achieved perfection not when there is nothing left to add, but when there is nothing left to take away.',
                'author' => 'Antoine de Saint-Exupéry',
            ],
            [
                'text'   => 'Without deviation from the norm, progress is not possible.',
                'author' => 'Frank Zappa',
            ],
            [
                'text'   => 'Make it simple, but significant.',
                'author' => 'Don Draper',
            ],
            [
                'text'   => 'Design is where science and art break even.',
                'author' => 'Robin Mathew',
            ],
            [
                'text'   => 'An idea can turn to dust or magic, depending on the talent that rubs against it.',
                'author' => 'Bill Bernbach',
            ],
            [
                'text'   => 'The secret of getting ahead is getting started.',
                'author' => 'Mark Twain',
            ],
            [
                'text'   => 'Colour is a power which directly influences the soul.',
                'author' => 'Wassily Kandinsky',
            ],
            [
                'text'   => 'Everything is designed. Few things are designed well.',
                'author' => 'Brian Reed',
            ],
            [
                'text'   => 'Great things are not done by impulse, but by a series of small things brought together.',
                'author' => 'Vincent van Gogh',
            ],
            [
                'text'   => 'The work you do while you procrastinate is probably the work you should be doing for the rest of your life.',
                'author' => 'Jessica Hische',
            ],
            [
                'text'   => 'Practice safe design: use a concept.',
                'author' => 'Petrula Vrontikis',
            ],
            [
                'text'   => 'Don\'t think about making art — just get it done. Let everyone else decide if it\'s good or bad, whether they love it or hate it.',
                'author' => 'Andy Warhol',
            ],
            [
                'text'   => 'Every artist was first an amateur.',
                'author' => 'Ralph Waldo Emerson',
            ],
            [
                'text'   => 'The most powerful person in the world is the storyteller.',
                'author' => 'Steve Jobs',
            ],
            [
                'text'   => 'Design is intelligence made visible.',
                'author' => 'Alina Wheeler',
            ],
        ];

        return $quotes[array_rand($quotes)];
    }

    public function performance(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePerfDateRange($request);

        $perf     = $this->perfStats('design', $from, $to);
        $perfMine = $this->perfStats('design', $from, $to, auth()->id());

        return view('dashboard.designer-performance', compact('perf', 'perfMine', 'from', 'to', 'period'));
    }
}
