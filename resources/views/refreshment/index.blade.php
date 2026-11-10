@extends('layouts.app')

@section('title', 'Refreshment')
@section('page-title')
    <i class="bi bi-controller me-2 text-primary"></i>Refreshment
@endsection

@push('styles')
<style>
/* ── Shared game chrome ───────────────────────────────────── */
.game-panel {
    max-width: 540px;
    margin: 0 auto;
}

/* ── Sudoku ───────────────────────────────────────────────── */
#sudoku-grid {
    display: grid;
    grid-template-columns: repeat(9, 1fr);
    gap: 1px;
    background: #333;
    border: 3px solid #333;
    border-radius: 4px;
    width: 100%;
    max-width: 414px;
    margin: 0 auto;
    aspect-ratio: 1;
}
.sudoku-cell {
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: clamp(.75rem, 2.5vw, 1.1rem);
    font-weight: 600;
    cursor: pointer;
    position: relative;
    aspect-ratio: 1;
    user-select: none;
    transition: background .1s;
}
/* Thick borders for 3×3 boxes */
.sudoku-cell:nth-child(3n)   { border-right: 2px solid #333; }
.sudoku-cell:nth-child(n+19):nth-child(-n+27),
.sudoku-cell:nth-child(n+46):nth-child(-n+54) { border-bottom: 2px solid #333; }

.sudoku-cell.given   { color: #1a3c5e; background: #f0f4fa; }
.sudoku-cell.selected { background: #cce5ff !important; }
.sudoku-cell.highlight { background: #e8f4fd; }
.sudoku-cell.peer    { background: #f0f4fa; }
.sudoku-cell.error   { color: #dc3545 !important; background: #fff5f5 !important; }
.sudoku-cell.correct { color: #198754 !important; }

#sudoku-numpad {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
    max-width: 300px;
    margin: 0 auto;
}
#sudoku-numpad button {
    aspect-ratio: 1;
    font-size: 1.1rem;
    font-weight: 700;
    border: 1.5px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    transition: background .12s, transform .08s;
}
#sudoku-numpad button:hover { background: #e9f1fc; transform: scale(1.05); }
#sudoku-numpad button.erase-btn { font-size: .85rem; color: #dc3545; border-color: #f5c6cb; }

/* ── Wordle ───────────────────────────────────────────────── */
#wordle-board {
    display: grid;
    grid-template-rows: repeat(6, 1fr);
    gap: 5px;
    max-width: 330px;
    margin: 0 auto;
}
.wordle-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 5px;
}
.wordle-tile {
    aspect-ratio: 1;
    border: 2px solid #d3d6da;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: clamp(1rem, 4vw, 1.5rem);
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    background: #fff;
    color: #1a1a1b;
    transition: background .3s, border-color .3s;
}
.wordle-tile.filled    { border-color: #878a8c; }
.wordle-tile.correct   { background: #538d4e; border-color: #538d4e; color: #fff; }
.wordle-tile.present   { background: #b59f3b; border-color: #b59f3b; color: #fff; }
.wordle-tile.absent    { background: #3a3a3c; border-color: #3a3a3c; color: #fff; }
.wordle-tile.pop { animation: tile-pop .1s ease; }
@keyframes tile-pop { 0%,100%{transform:scale(1)} 50%{transform:scale(1.08)} }
.wordle-tile.shake { animation: tile-shake .4s ease; }
@keyframes tile-shake {
    0%,100%{transform:translateX(0)}
    20%{transform:translateX(-6px)}
    40%{transform:translateX(6px)}
    60%{transform:translateX(-4px)}
    80%{transform:translateX(4px)}
}
.wordle-tile.flip { animation: tile-flip .5s ease forwards; }
@keyframes tile-flip {
    0%{transform:rotateX(0)}
    50%{transform:rotateX(-90deg)}
    100%{transform:rotateX(0)}
}

#wordle-keyboard {
    display: flex;
    flex-direction: column;
    gap: 5px;
    max-width: 480px;
    margin: 0 auto;
}
.wordle-kb-row {
    display: flex;
    justify-content: center;
    gap: 4px;
}
.wordle-key {
    height: 56px;
    min-width: 36px;
    padding: 0 6px;
    border: none;
    border-radius: 4px;
    background: #d3d6da;
    color: #1a1a1b;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
    user-select: none;
}
.wordle-key.wide { min-width: 56px; font-size: .75rem; }
.wordle-key:hover { filter: brightness(.92); }
.wordle-key.correct { background: #538d4e; color: #fff; }
.wordle-key.present { background: #b59f3b; color: #fff; }
.wordle-key.absent  { background: #3a3a3c; color: #fff; }

#wordle-msg {
    min-height: 1.6rem;
    font-weight: 600;
    font-size: .9rem;
    text-align: center;
    color: #1a1a1b;
}

/* ── Crossword ────────────────────────────────────────────── */
#crossword-wrap {
    overflow-x: auto;
}
#crossword-grid {
    display: inline-grid;
    gap: 1px;
    background: #333;
    border: 2px solid #333;
    border-radius: 3px;
}
.cw-cell {
    width: clamp(28px, 5vw, 38px);
    height: clamp(28px, 5vw, 38px);
    background: #fff;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cw-cell.black { background: #1a1a1b; }
.cw-cell.selected { background: #cce5ff; }
.cw-cell.highlight { background: #e8f4fd; }
.cw-cell input {
    width: 100%; height: 100%;
    border: none; outline: none;
    background: transparent;
    text-align: center;
    text-transform: uppercase;
    font-size: clamp(.6rem, 2.5vw, .95rem);
    font-weight: 700;
    color: #1a1a1b;
    caret-color: transparent;
    cursor: default;
}
.cw-cell input.given { color: #1a3c5e; }
.cw-cell input.error { color: #dc3545; }
.cw-cell input.correct { color: #198754; }
.cw-num {
    position: absolute;
    top: 1px; left: 2px;
    font-size: clamp(.45rem, 1.4vw, .6rem);
    font-weight: 700;
    color: #555;
    line-height: 1;
    pointer-events: none;
}
#cw-clues { column-count: 1; }
@media (min-width: 576px) { #cw-clues { column-count: 2; } }
.cw-clue { font-size: .82rem; padding: .1rem 0; cursor: pointer; border-radius: 3px; }
.cw-clue:hover, .cw-clue.active { background: #e8f4fd; }
</style>
@endpush

@section('content')

<p class="text-muted mb-4" style="font-size:.9rem">
    <i class="bi bi-stars me-1 text-warning"></i>
    Take a quick break. Your puzzles reset every day.
</p>

{{-- ── Game tabs ──────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4" id="gameTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="tab-sudoku"
                data-bs-toggle="tab" data-bs-target="#panel-sudoku"
                type="button" role="tab">
            <i class="bi bi-grid-3x3 me-1"></i>Sudoku
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-wordle"
                data-bs-toggle="tab" data-bs-target="#panel-wordle"
                type="button" role="tab">
            <i class="bi bi-alphabet me-1"></i>Wordle
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-crossword"
                data-bs-toggle="tab" data-bs-target="#panel-crossword"
                type="button" role="tab">
            <i class="bi bi-layout-text-window me-1"></i>Crossword
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ════════════════════════════════════════════
         SUDOKU
    ════════════════════════════════════════════ --}}
    <div class="tab-pane fade show active" id="panel-sudoku" role="tabpanel">
        <div class="game-panel">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex gap-2">
                    <span class="badge bg-light border text-secondary" id="sudoku-difficulty">Medium</span>
                    <span class="badge bg-light border text-secondary" id="sudoku-timer">0:00</span>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="sudoku-level" style="width:auto">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" id="sudoku-new-btn">
                        <i class="bi bi-arrow-clockwise me-1"></i>New
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="sudoku-hint-btn">
                        <i class="bi bi-lightbulb me-1"></i>Hint
                    </button>
                </div>
            </div>

            <div id="sudoku-grid"></div>

            <div class="mt-3" id="sudoku-numpad">
                @for($n = 1; $n <= 9; $n++)
                    <button class="num-btn" data-num="{{ $n }}">{{ $n }}</button>
                @endfor
                <button class="erase-btn" id="sudoku-erase">✕</button>
            </div>

            <div id="sudoku-msg" class="text-center mt-3 fw-semibold" style="min-height:1.5rem"></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         WORDLE
    ════════════════════════════════════════════ --}}
    <div class="tab-pane fade" id="panel-wordle" role="tabpanel">
        <div class="game-panel">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div id="wordle-msg"></div>
                <button class="btn btn-sm btn-outline-primary ms-auto" id="wordle-new-btn">
                    <i class="bi bi-arrow-clockwise me-1"></i>New Word
                </button>
            </div>

            <div id="wordle-board" class="mb-4"></div>

            <div id="wordle-keyboard"></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         CROSSWORD
    ════════════════════════════════════════════ --}}
    <div class="tab-pane fade" id="panel-crossword" role="tabpanel">
        <div class="row g-4">
            <div class="col-12 col-md-auto">
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                    <span class="badge bg-light border text-secondary" id="cw-timer">0:00</span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" id="cw-check-btn">
                            <i class="bi bi-check2 me-1"></i>Check
                        </button>
                        <button class="btn btn-sm btn-outline-primary" id="cw-new-btn">
                            <i class="bi bi-arrow-clockwise me-1"></i>New
                        </button>
                    </div>
                </div>
                <div id="crossword-wrap">
                    <div id="crossword-grid"></div>
                </div>
                <div id="cw-msg" class="mt-2 fw-semibold text-center" style="min-height:1.4rem;font-size:.88rem"></div>
            </div>
            <div class="col">
                <div id="cw-clues">
                    <div class="mb-3">
                        <div class="fw-semibold mb-1" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;color:#6c757d">Across</div>
                        <div id="cw-clues-across"></div>
                    </div>
                    <div>
                        <div class="fw-semibold mb-1" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;color:#6c757d">Down</div>
                        <div id="cw-clues-down"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /tab-content --}}

@endsection

@push('scripts')
<script>
// ════════════════════════════════════════════════════
// SUDOKU ENGINE
// ════════════════════════════════════════════════════
(function () {
    'use strict';

    // ── Solver (backtracking) ─────────────────────────────────
    function candidates(board, idx) {
        const row = Math.floor(idx / 9), col = idx % 9;
        const used = new Set();
        for (let c = 0; c < 9; c++) used.add(board[row * 9 + c]);
        for (let r = 0; r < 9; r++) used.add(board[r * 9 + col]);
        const br = Math.floor(row / 3) * 3, bc = Math.floor(col / 3) * 3;
        for (let r = br; r < br + 3; r++)
            for (let c = bc; c < bc + 3; c++)
                used.add(board[r * 9 + c]);
        const res = [];
        for (let n = 1; n <= 9; n++) if (!used.has(n)) res.push(n);
        return res;
    }

    function solve(board, limit = 2) {
        let count = 0;
        function bt(b) {
            const idx = b.indexOf(0);
            if (idx === -1) { count++; return count < limit; }
            for (const n of candidates(b, idx)) {
                b[idx] = n;
                if (!bt(b)) return false;
                b[idx] = 0;
            }
            return true;
        }
        const copy = [...board];
        bt(copy);
        return { solutions: count, solved: copy };
    }

    function shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function generate() {
        // Build a full solved board
        const board = Array(81).fill(0);
        function fill(idx) {
            if (idx === 81) return true;
            const nums = shuffle([1,2,3,4,5,6,7,8,9]);
            for (const n of nums) {
                if (candidates(board, idx).includes(n)) {
                    board[idx] = n;
                    if (fill(idx + 1)) return true;
                    board[idx] = 0;
                }
            }
            return false;
        }
        fill(0);
        return board;
    }

    function makePuzzle(solution, removals) {
        const puzzle = [...solution];
        const idxs   = shuffle([...Array(81).keys()]);
        let removed  = 0;
        for (const idx of idxs) {
            if (removed >= removals) break;
            const val = puzzle[idx];
            puzzle[idx] = 0;
            if (solve(puzzle).solutions !== 1) {
                puzzle[idx] = val; // revert — would create multiple solutions
            } else {
                removed++;
            }
        }
        return puzzle;
    }

    // ── State ─────────────────────────────────────────────────
    let solution = [], puzzle = [], userBoard = [], selected = null;
    let timerInterval = null, seconds = 0, done = false;
    const REMOVALS = { easy: 35, medium: 46, hard: 54 };

    function startTimer() {
        clearInterval(timerInterval);
        seconds = 0;
        timerInterval = setInterval(() => {
            if (done) return;
            seconds++;
            const m = Math.floor(seconds / 60), s = seconds % 60;
            document.getElementById('sudoku-timer').textContent =
                `${m}:${String(s).padStart(2,'0')}`;
        }, 1000);
    }

    function newGame() {
        const level = document.getElementById('sudoku-level').value;
        document.getElementById('sudoku-difficulty').textContent =
            level.charAt(0).toUpperCase() + level.slice(1);
        document.getElementById('sudoku-msg').textContent = '';
        done = false;
        solution  = generate();
        puzzle    = makePuzzle(solution, REMOVALS[level]);
        userBoard = [...puzzle];
        selected  = null;
        startTimer();
        render();
    }

    // ── Render ────────────────────────────────────────────────
    function render() {
        const grid = document.getElementById('sudoku-grid');
        grid.innerHTML = '';
        for (let i = 0; i < 81; i++) {
            const cell = document.createElement('div');
            cell.className = 'sudoku-cell';
            cell.dataset.idx = i;

            const given = puzzle[i] !== 0;
            const val   = userBoard[i];

            if (given) {
                cell.textContent = val;
                cell.classList.add('given');
            } else if (val !== 0) {
                cell.textContent = val;
                if (val === solution[i]) cell.classList.add('correct');
                else cell.classList.add('error');
            }

            if (selected === i) {
                cell.classList.add('selected');
            } else if (selected !== null) {
                const sr = Math.floor(selected / 9), sc = selected % 9;
                const cr = Math.floor(i / 9), cc = i % 9;
                const sbr = Math.floor(sr / 3), sbc = Math.floor(sc / 3);
                const cbr = Math.floor(cr / 3), cbc = Math.floor(cc / 3);
                if (sr === cr || sc === cc || (sbr === cbr && sbc === cbc))
                    cell.classList.add('highlight');
                // Highlight same number
                if (userBoard[selected] && userBoard[selected] === val)
                    cell.classList.add('peer');
            }

            cell.addEventListener('click', () => {
                selected = i;
                render();
            });
            grid.appendChild(cell);
        }
    }

    // ── Input ─────────────────────────────────────────────────
    document.getElementById('sudoku-numpad').addEventListener('click', e => {
        const btn = e.target.closest('[data-num]');
        if (btn && selected !== null && puzzle[selected] === 0) {
            userBoard[selected] = parseInt(btn.dataset.num);
            render();
            checkWin();
        }
    });

    document.getElementById('sudoku-erase').addEventListener('click', () => {
        if (selected !== null && puzzle[selected] === 0) {
            userBoard[selected] = 0;
            render();
        }
    });

    document.addEventListener('keydown', e => {
        if (!document.getElementById('panel-sudoku').classList.contains('show')) return;
        if (e.key >= '1' && e.key <= '9' && selected !== null && puzzle[selected] === 0) {
            userBoard[selected] = parseInt(e.key);
            render();
            checkWin();
        }
        if ((e.key === 'Backspace' || e.key === 'Delete' || e.key === '0') && selected !== null && puzzle[selected] === 0) {
            userBoard[selected] = 0;
            render();
        }
        // Arrow navigation
        const dirs = { ArrowUp: -9, ArrowDown: 9, ArrowLeft: -1, ArrowRight: 1 };
        if (dirs[e.key] !== undefined && selected !== null) {
            const next = selected + dirs[e.key];
            if (next >= 0 && next < 81) { selected = next; render(); }
            e.preventDefault();
        }
    });

    document.getElementById('sudoku-new-btn').addEventListener('click', newGame);

    document.getElementById('sudoku-hint-btn').addEventListener('click', () => {
        if (done) return;
        // Find a random unsolved incorrect cell and reveal it
        const unsolved = [];
        for (let i = 0; i < 81; i++) {
            if (userBoard[i] !== solution[i]) unsolved.push(i);
        }
        if (!unsolved.length) return;
        const idx = unsolved[Math.floor(Math.random() * unsolved.length)];
        userBoard[idx] = solution[idx];
        selected = idx;
        render();
        checkWin();
    });

    function checkWin() {
        for (let i = 0; i < 81; i++) {
            if (userBoard[i] !== solution[i]) return;
        }
        done = true;
        clearInterval(timerInterval);
        const m = Math.floor(seconds / 60), s = seconds % 60;
        document.getElementById('sudoku-msg').innerHTML =
            `<span class="text-success"><i class="bi bi-trophy-fill me-1"></i>Solved in ${m}:${String(s).padStart(2,'0')}! 🎉</span>`;
    }

    newGame();
}());


// ════════════════════════════════════════════════════
// WORDLE ENGINE
// ════════════════════════════════════════════════════
(function () {
    'use strict';

    // 150-word everyday vocabulary list (5-letter, common English words)
    const WORDS = [
        'ABOUT','ABOVE','ABUSE','ACTOR','ACUTE','ADMIT','ADOPT','ADULT','AFTER','AGAIN',
        'AGENT','AGREE','AHEAD','AIMED','ALARM','ALBUM','ALERT','ALIEN','ALIGN','ALIKE',
        'ALIVE','ALLEY','ALLOW','ALONE','ALONG','ALTER','ANGEL','ANGER','ANGLE','ANGRY',
        'ANIME','ANKLE','ANNEX','APART','APPLE','APPLY','ARENA','ARGUE','ARISE','ARMOR',
        'ARRAY','ARROW','ARSON','ASKED','ATTIC','AUDIO','AUDIT','AVOID','AWARD','AWARE',
        'AWFUL','BASIC','BEACH','BEGAN','BEGIN','BEING','BELOW','BENCH','BERRY','BIBLE',
        'BLACK','BLADE','BLAME','BLAND','BLANK','BLAST','BLAZE','BLEED','BLEND','BLESS',
        'BLIND','BLOCK','BLOOD','BLOOM','BLOWN','BLUES','BLUNT','BOARD','BONUS','BOOST',
        'BOOTH','BOUND','BOXER','BRACE','BRAIN','BRAND','BRAVE','BREAD','BREAK','BREED',
        'BRIBE','BRIDE','BRIEF','BRING','BROAD','BROOK','BROWN','BUILD','BUILT','BURST',
        'CABIN','CAMEL','CARRY','CATCH','CAUSE','CHAIN','CHAIR','CHALK','CHEAP','CHECK',
        'CHEEK','CHESS','CHEST','CHIEF','CHILD','CHINA','CHOIR','CHOSE','CIVIC','CIVIL',
        'CLAIM','CLASH','CLASS','CLEAN','CLEAR','CLICK','CLIMB','CLING','CLOCK','CLOSE',
        'CLOUD','COACH','COAST','COLOR','COMIC','COMMA','CORAL','COULD','COUNT','COURT',
        'COVER','CRACK','CRAFT','CRASH','CRAZY','CREAM','CREEK','CRIME','CRISP','CROSS',
    ];

    const KEYBOARD_ROWS = [
        ['Q','W','E','R','T','Y','U','I','O','P'],
        ['A','S','D','F','G','H','J','K','L'],
        ['ENTER','Z','X','C','V','B','N','M','⌫'],
    ];

    let target = '', guesses = [], current = '', gameOver = false;

    function newGame() {
        target   = WORDS[Math.floor(Math.random() * WORDS.length)];
        guesses  = [];
        current  = '';
        gameOver = false;
        document.getElementById('wordle-msg').textContent = '';
        buildBoard();
        buildKeyboard();
    }

    // ── Board ─────────────────────────────────────────────────
    function buildBoard() {
        const board = document.getElementById('wordle-board');
        board.innerHTML = '';
        for (let r = 0; r < 6; r++) {
            const row = document.createElement('div');
            row.className = 'wordle-row';
            row.id = `wr-${r}`;
            for (let c = 0; c < 5; c++) {
                const tile = document.createElement('div');
                tile.className = 'wordle-tile';
                tile.id = `wt-${r}-${c}`;
                row.appendChild(tile);
            }
            board.appendChild(row);
        }
    }

    function updateCurrentRow() {
        for (let c = 0; c < 5; c++) {
            const tile = document.getElementById(`wt-${guesses.length}-${c}`);
            if (!tile) return;
            tile.textContent  = current[c] || '';
            tile.className    = 'wordle-tile' + (current[c] ? ' filled' : '');
            if (current[c]) { tile.classList.add('pop'); setTimeout(() => tile.classList.remove('pop'), 120); }
        }
    }

    // ── Keyboard ──────────────────────────────────────────────
    function buildKeyboard() {
        const kb = document.getElementById('wordle-keyboard');
        kb.innerHTML = '';
        KEYBOARD_ROWS.forEach(row => {
            const div = document.createElement('div');
            div.className = 'wordle-kb-row';
            row.forEach(key => {
                const btn = document.createElement('button');
                btn.className = 'wordle-key' + (key.length > 1 ? ' wide' : '');
                btn.textContent = key;
                btn.dataset.key = key;
                btn.addEventListener('click', () => handleKey(key));
                div.appendChild(btn);
            });
            kb.appendChild(div);
        });
    }

    function colorKey(letter, state) {
        const priority = { correct: 3, present: 2, absent: 1 };
        document.querySelectorAll(`.wordle-key[data-key="${letter}"]`).forEach(btn => {
            const cur = priority[btn.dataset.state] || 0;
            if ((priority[state] || 0) > cur) {
                btn.classList.remove('correct', 'present', 'absent');
                btn.classList.add(state);
                btn.dataset.state = state;
            }
        });
    }

    // ── Game logic ────────────────────────────────────────────
    function handleKey(key) {
        if (gameOver) return;
        if (key === '⌫' || key === 'Backspace') {
            current = current.slice(0, -1);
            updateCurrentRow();
            return;
        }
        if (key === 'ENTER' || key === 'Enter') {
            submitGuess();
            return;
        }
        if (/^[A-Z]$/.test(key) && current.length < 5) {
            current += key;
            updateCurrentRow();
        }
    }

    function submitGuess() {
        if (current.length !== 5) {
            shakeRow(guesses.length);
            showMsg('Not enough letters');
            return;
        }
        // Accept any 5-letter string (no dictionary check — keeps it fun offline)
        const result = scoreguess(current, target);
        const rowIdx = guesses.length;
        guesses.push(current);

        // Reveal tiles with flip animation
        result.forEach((state, c) => {
            const tile = document.getElementById(`wt-${rowIdx}-${c}`);
            setTimeout(() => {
                tile.classList.add('flip');
                setTimeout(() => {
                    tile.textContent = current[c];
                    tile.className   = `wordle-tile ${state}`;
                }, 250);
            }, c * 100);
        });

        setTimeout(() => {
            result.forEach((state, c) => colorKey(current[c], state));

            if (current === target) {
                gameOver = true;
                const msgs = ['Genius!','Magnificent!','Impressive!','Splendid!','Great!','Phew!'];
                showMsg(msgs[Math.min(rowIdx, 5)] + ' 🎉');
            } else if (guesses.length === 6) {
                gameOver = true;
                showMsg(`The word was ${target}`);
            }
        }, 600);

        current = '';
    }

    function scoreguess(guess, answer) {
        const result  = Array(5).fill('absent');
        const ansLeft = [...answer];
        // First pass: correct
        for (let i = 0; i < 5; i++) {
            if (guess[i] === answer[i]) {
                result[i]  = 'correct';
                ansLeft[i] = null;
            }
        }
        // Second pass: present
        for (let i = 0; i < 5; i++) {
            if (result[i] === 'correct') continue;
            const j = ansLeft.indexOf(guess[i]);
            if (j !== -1) { result[i] = 'present'; ansLeft[j] = null; }
        }
        return result;
    }

    function shakeRow(rowIdx) {
        const row = document.getElementById(`wr-${rowIdx}`);
        row?.querySelectorAll('.wordle-tile').forEach(t => {
            t.classList.add('shake');
            t.addEventListener('animationend', () => t.classList.remove('shake'), { once: true });
        });
    }

    let msgTimer;
    function showMsg(text) {
        const el = document.getElementById('wordle-msg');
        el.textContent = text;
        clearTimeout(msgTimer);
        if (!gameOver) msgTimer = setTimeout(() => el.textContent = '', 2000);
    }

    // ── Physical keyboard ─────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (!document.getElementById('panel-wordle').classList.contains('show')) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.key === 'Enter')     handleKey('Enter');
        else if (e.key === 'Backspace') handleKey('Backspace');
        else if (/^[a-zA-Z]$/.test(e.key)) handleKey(e.key.toUpperCase());
    });

    document.getElementById('wordle-new-btn').addEventListener('click', newGame);

    newGame();
}());


// ════════════════════════════════════════════════════
// CROSSWORD ENGINE
// ════════════════════════════════════════════════════
(function () {
    'use strict';

    // ── Puzzle bank ───────────────────────────────────────────
    // Each puzzle: grid (. = black, letter = given/answer), clues.
    // FORMAT: 13×13 grids (standard mini-crossword style but bigger).
    // We define several and pick one randomly each day (by day-of-year).
    const PUZZLES = [
        {
            // Puzzle 1 — Design / Fashion theme
            cols: 11, rows: 11,
            // Grid row by row: '#' = black, '.' = white cell
            layout: [
                '#.........#',
                '..###.###..',
                '..#...#....',
                '..#.#.#.###',
                '.......#...',
                '###.#.#.###',
                '...#.......', 
                '###.#.#.#..',
                '....#...#..',
                '..###.###..',
                '#.........#',
            ],
            // Answers fill white cells row by row in reading order
            answers: {
                across: {
                    1:  { row:0, col:1, len:9, word:'BLUEPRINT', clue:'Design plan' },
                    5:  { row:2, col:2, len:1, word:'D', clue:'Design abbr.' },  // placeholder filler
                    6:  { row:4, col:0, len:7, word:'PATTERN', clue:'Repeated motif in fabric' },
                    7:  { row:6, col:0, len:3, word:'DYE', clue:'Color a fabric' },
                    8:  { row:6, col:4, len:7, word:'TEXTURE', clue:'Feel of a surface' },
                    9:  { row:8, col:0, len:4, word:'MOCK', clue:'Test version of a design' },
                    10: { row:8, col:5, len:3, word:'ART', clue:'Creative work' },
                    11: { row:10,col:1, len:9, word:'TEMPLATES', clue:'Reusable design frames' },
                },
                down: {
                    1:  { row:0, col:1, len:9, word:'DRAFTING', clue:'Drawing technical plans', dir:'down' },
                    2:  { row:0, col:5, len:11,word:'PROPORTION', clue:'Ratio of parts', dir:'down' },
                    3:  { row:0, col:9, len:9, word:'ALIGNMENT', clue:'Lining elements up', dir:'down' },
                    4:  { row:2, col:3, len:7, word:'BRANDING', clue:'Visual identity system', dir:'down' },
                },
            },
            // Simplified: we just embed the full solved grid as a 2D char array
            // and derive clue numbering from it.
            // For simplicity this engine uses pre-numbered grids below.
            usePrebuilt: true,
        },
    ];

    // ── Pre-built puzzle (clean, fully specified) ─────────────
    // Using a compact 11×11 crossword with a design/creative theme.
    // '0' = black cell, letters = answer letters.
    const PREBUILT = [
        {
            cols: 11, rows: 11,
            grid: [
                '0BLUEPRINT0',
                'DR000P000EM',
                'AN0CLUE0AOP',
                'FT0L0E0RINT',
                'TONE0SKETCH',
                '000I0O0E000',
                'GRADIENTS00',
                '000N0R0000A',
                'MOCK0ARTSY0',
                'UP000S000YL',
                '0TEMPLATES0',
            ],
            clues: {
                across: [
                    { n:1,  row:0, col:1, len:9, clue:'Detailed design plan (9)' },
                    { n:5,  row:2, col:2, len:3, clue:'Hint to solve a puzzle (3)' },
                    { n:6,  row:3, col:7, len:4, clue:'Printed letters (4)' },
                    { n:7,  row:4, col:0, len:4, clue:'Shade of a colour (4)' },
                    { n:8,  row:4, col:5, len:6, clue:'Quick rough drawing (6)' },
                    { n:9,  row:6, col:0, len:9, clue:'Smooth colour transitions (9)' },
                    { n:10, row:8, col:0, len:4, clue:'Prototype model (4)' },
                    { n:11, row:8, col:5, len:5, clue:'Visually creative style (5)' },
                    { n:12, row:10,col:1, len:9, clue:'Reusable design frames (9)' },
                ],
                down: [
                    { n:1,  row:0, col:1, len:9, clue:'Technical drawing practice (9)' },
                    { n:2,  row:0, col:5, len:9, clue:'Ratio of elements (9)' },
                    { n:3,  row:0, col:9, len:9, clue:'Lining things up (9)' },
                    { n:4,  row:1, col:10,len:9, clue:'Visual identity system (9)' },
                    { n:5,  row:2, col:3, len:7, clue:'Story framework (7)' },
                    { n:6,  row:3, col:7, len:7, clue:'Colour scheme plan (7)' },
                ],
            },
        },
        {
            cols: 11, rows: 11,
            grid: [
                '0WORKSPACE0',
                'FO000K000RE',
                'ON0IDEA0ANA',
                'CU0S0E0RUST',
                'USECOLOR000',
                '000I0U0R000',
                '0ARTBOARDS0',
                '000N000000D',
                'ICONS0LAYER',
                'DE000A000ES',
                '0THUMBNAIL0',
            ],
            clues: {
                across: [
                    { n:1,  row:0, col:1, len:9, clue:'Designer\'s desk environment (9)' },
                    { n:5,  row:2, col:2, len:4, clue:'Creative spark or concept (4)' },
                    { n:6,  row:3, col:7, len:4, clue:'Brown metallic corrosion (4)' },
                    { n:7,  row:4, col:0, len:3, clue:'Put into action (3)' },
                    { n:8,  row:4, col:3, len:5, clue:'Apply a hue (5)' },
                    { n:9,  row:6, col:1, len:9, clue:'Adobe Illustrator canvases (9)' },
                    { n:10, row:8, col:0, len:5, clue:'Small symbolic images (5)' },
                    { n:11, row:8, col:6, len:5, clue:'Stack level in Photoshop (5)' },
                    { n:12, row:10,col:1, len:9, clue:'Small preview image (9)' },
                ],
                down: [
                    { n:1,  row:0, col:1, len:9, clue:'Sharpen or refine (5)' },
                    { n:2,  row:0, col:5, len:9, clue:'Keyboard shortcut key (5)' },
                    { n:3,  row:0, col:9, len:9, clue:'Final version (9)' },
                    { n:4,  row:1, col:10,len:9, clue:'Rework a design (9)' },
                    { n:5,  row:2, col:3, len:7, clue:'Design rule or guideline (7)' },
                    { n:6,  row:3, col:7, len:7, clue:'Colour variation (7)' },
                ],
            },
        },
    ];

    // ── State ─────────────────────────────────────────────────
    let puzzle = null, userGrid = [], selected = null, selectedDir = 'across';
    let cwTimerInterval = null, cwSeconds = 0, cwDone = false;

    function todayPuzzle() {
        const d   = new Date();
        const doy = Math.floor((d - new Date(d.getFullYear(), 0, 0)) / 86400000);
        return PREBUILT[doy % PREBUILT.length];
    }

    function newGame() {
        puzzle   = todayPuzzle();
        cwDone   = false;
        userGrid = puzzle.grid.map(row =>
            row.split('').map(ch => ({ given: false, val: '', answer: ch === '0' ? '#' : ch }))
        );
        selected = null;
        document.getElementById('cw-msg').textContent = '';
        startCwTimer();
        renderGrid();
        renderClues();
    }

    // ── Timer ─────────────────────────────────────────────────
    function startCwTimer() {
        clearInterval(cwTimerInterval);
        cwSeconds = 0;
        cwTimerInterval = setInterval(() => {
            if (cwDone) return;
            cwSeconds++;
            const m = Math.floor(cwSeconds / 60), s = cwSeconds % 60;
            document.getElementById('cw-timer').textContent =
                `${m}:${String(s).padStart(2,'0')}`;
        }, 1000);
    }

    // ── Render grid ───────────────────────────────────────────
    function renderGrid() {
        const container = document.getElementById('crossword-grid');
        container.style.gridTemplateColumns = `repeat(${puzzle.cols}, auto)`;
        container.innerHTML = '';

        // Build clue-number map: { 'row,col': number }
        const numMap = {};
        [...puzzle.clues.across, ...puzzle.clues.down].forEach(c => {
            const key = `${c.row},${c.col}`;
            if (!numMap[key]) numMap[key] = c.n;
        });

        for (let r = 0; r < puzzle.rows; r++) {
            for (let c = 0; c < puzzle.cols; c++) {
                const cellData = userGrid[r][c];
                const div = document.createElement('div');
                div.className = 'cw-cell';
                div.dataset.row = r;
                div.dataset.col = c;

                if (cellData.answer === '#') {
                    div.classList.add('black');
                    container.appendChild(div);
                    continue;
                }

                // Clue number
                const key = `${r},${c}`;
                if (numMap[key]) {
                    const num = document.createElement('span');
                    num.className   = 'cw-num';
                    num.textContent = numMap[key];
                    div.appendChild(num);
                }

                const inp = document.createElement('input');
                inp.maxLength = 1;
                inp.type      = 'text';
                inp.inputMode = 'text';
                if (cellData.val) {
                    inp.value     = cellData.val;
                    inp.className = 'correct';
                }

                inp.addEventListener('focus', () => selectCell(r, c));
                inp.addEventListener('input', e => handleCwInput(e, r, c));
                inp.addEventListener('keydown', e => handleCwKey(e, r, c));

                div.appendChild(inp);
                container.appendChild(div);
            }
        }
        highlightSelected();
    }

    function getCell(r, c) {
        return document.querySelector(`.cw-cell[data-row="${r}"][data-col="${c}"]`);
    }
    function getInput(r, c) {
        return getCell(r, c)?.querySelector('input');
    }

    function highlightSelected() {
        document.querySelectorAll('.cw-cell').forEach(c => {
            c.classList.remove('selected', 'highlight');
        });
        if (selected === null) return;

        const [sr, sc] = selected;
        getCell(sr, sc)?.classList.add('selected');

        // Highlight the word the selected cell belongs to
        const clue = getActiveClue(sr, sc, selectedDir);
        if (clue) {
            if (selectedDir === 'across') {
                for (let c = clue.col; c < clue.col + clue.len; c++) {
                    if (c !== sc || sr !== sr) getCell(sr, c)?.classList.add('highlight');
                }
            } else {
                for (let r = clue.row; r < clue.row + clue.len; r++) {
                    getCell(r, sc)?.classList.add('highlight');
                }
            }
        }
    }

    function getActiveClue(r, c, dir) {
        const list = dir === 'across' ? puzzle.clues.across : puzzle.clues.down;
        return list.find(cl => {
            if (dir === 'across') return cl.row === r && c >= cl.col && c < cl.col + cl.len;
            return cl.col === c && r >= cl.row && r < cl.row + cl.len;
        }) || null;
    }

    function selectCell(r, c) {
        if (selected && selected[0] === r && selected[1] === c) {
            // Toggle direction
            const hasAcross = !!getActiveClue(r, c, 'across');
            const hasDown   = !!getActiveClue(r, c, 'down');
            if (hasAcross && hasDown) selectedDir = selectedDir === 'across' ? 'down' : 'across';
        } else {
            selected = [r, c];
            // Auto-pick direction: prefer across
            const hasAcross = !!getActiveClue(r, c, 'across');
            if (!hasAcross) selectedDir = 'down';
        }
        selected = [r, c];
        highlightSelected();
        // Highlight clue in list
        highlightClue();
    }

    function handleCwInput(e, r, c) {
        const inp = getInput(r, c);
        const ch  = inp.value.slice(-1).toUpperCase();
        inp.value = ch;
        if (ch) {
            userGrid[r][c].val = ch;
            inp.className = ch === userGrid[r][c].answer ? 'correct' : '';
            advanceCursor(r, c);
        }
    }

    function handleCwKey(e, r, c) {
        if (e.key === 'Backspace') {
            const inp = getInput(r, c);
            if (inp.value) {
                inp.value = '';
                userGrid[r][c].val = '';
                inp.className = '';
            } else {
                retreatCursor(r, c);
            }
            e.preventDefault();
        }
        const moves = {
            ArrowRight: [0, 1], ArrowLeft: [0, -1],
            ArrowDown:  [1, 0], ArrowUp:  [-1, 0],
        };
        if (moves[e.key]) {
            const [dr, dc] = moves[e.key];
            moveCursor(r + dr, c + dc);
            e.preventDefault();
        }
    }

    function advanceCursor(r, c) {
        if (selectedDir === 'across') moveCursor(r, c + 1);
        else moveCursor(r + 1, c);
    }
    function retreatCursor(r, c) {
        if (selectedDir === 'across') moveCursor(r, c - 1);
        else moveCursor(r - 1, c);
    }
    function moveCursor(r, c) {
        if (r < 0 || r >= puzzle.rows || c < 0 || c >= puzzle.cols) return;
        if (userGrid[r][c].answer === '#') return;
        selected = [r, c];
        highlightSelected();
        highlightClue();
        getInput(r, c)?.focus();
    }

    // ── Clue list ─────────────────────────────────────────────
    function renderClues() {
        const acrossEl = document.getElementById('cw-clues-across');
        const downEl   = document.getElementById('cw-clues-down');
        acrossEl.innerHTML = '';
        downEl.innerHTML   = '';

        puzzle.clues.across.forEach(cl => {
            const div = document.createElement('div');
            div.className   = 'cw-clue';
            div.dataset.n   = cl.n;
            div.dataset.dir = 'across';
            div.textContent = `${cl.n}. ${cl.clue}`;
            div.addEventListener('click', () => {
                selectedDir = 'across';
                moveCursor(cl.row, cl.col);
            });
            acrossEl.appendChild(div);
        });

        puzzle.clues.down.forEach(cl => {
            const div = document.createElement('div');
            div.className   = 'cw-clue';
            div.dataset.n   = cl.n;
            div.dataset.dir = 'down';
            div.textContent = `${cl.n}. ${cl.clue}`;
            div.addEventListener('click', () => {
                selectedDir = 'down';
                moveCursor(cl.row, cl.col);
            });
            downEl.appendChild(div);
        });
    }

    function highlightClue() {
        document.querySelectorAll('.cw-clue').forEach(el => el.classList.remove('active'));
        if (!selected) return;
        const [r, c] = selected;
        const cl = getActiveClue(r, c, selectedDir);
        if (cl) {
            const el = document.querySelector(`.cw-clue[data-n="${cl.n}"][data-dir="${selectedDir}"]`);
            el?.classList.add('active');
            el?.scrollIntoView({ block: 'nearest' });
        }
    }

    // ── Check ─────────────────────────────────────────────────
    document.getElementById('cw-check-btn').addEventListener('click', () => {
        let wrong = 0, empty = 0, right = 0;
        for (let r = 0; r < puzzle.rows; r++) {
            for (let c = 0; c < puzzle.cols; c++) {
                const cd  = userGrid[r][c];
                if (cd.answer === '#') continue;
                const inp = getInput(r, c);
                if (!inp) continue;
                if (!inp.value) { empty++; continue; }
                if (inp.value.toUpperCase() === cd.answer) {
                    inp.className = 'correct'; right++;
                } else {
                    inp.className = 'error'; wrong++;
                }
            }
        }
        const msg = document.getElementById('cw-msg');
        if (wrong === 0 && empty === 0) {
            cwDone = true;
            clearInterval(cwTimerInterval);
            const m = Math.floor(cwSeconds/60), s = cwSeconds%60;
            msg.innerHTML = `<span class="text-success"><i class="bi bi-trophy-fill me-1"></i>Solved in ${m}:${String(s).padStart(2,'0')}! 🎉</span>`;
        } else if (wrong > 0) {
            msg.innerHTML = `<span class="text-danger">${wrong} mistake(s) found</span>`;
        } else {
            msg.innerHTML = `<span class="text-muted">${empty} cell(s) still empty</span>`;
        }
    });

    document.getElementById('cw-new-btn').addEventListener('click', () => {
        // Rotate to next puzzle
        const idx = PREBUILT.indexOf(puzzle);
        puzzle = PREBUILT[(idx + 1) % PREBUILT.length];
        cwDone = false;
        userGrid = puzzle.grid.map(row =>
            row.split('').map(ch => ({ given: false, val: '', answer: ch === '0' ? '#' : ch }))
        );
        selected = null;
        document.getElementById('cw-msg').textContent = '';
        startCwTimer();
        renderGrid();
        renderClues();
    });

    newGame();
}());
</script>
@endpush
