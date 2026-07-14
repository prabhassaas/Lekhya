{{-- ── Floating scientific calculator (authenticated users only) ─────────── --}}
<div x-data="lekhyaCalc" x-cloak>

    {{-- Floating toggle button --}}
    <button type="button" x-show="!open" @click="toggle()"
            title="Calculator (Alt+C)"
            style="position:fixed;right:20px;bottom:20px;z-index:9000;width:52px;height:52px"
            class="rounded-full bg-navy-600 hover:bg-navy-700 text-white shadow-lg flex items-center justify-center transition hover:scale-105">
        <i class="fa fa-calculator text-lg"></i>
    </button>

    {{-- Calculator panel --}}
    <div x-show="open" x-ref="panel" x-transition
         :style="panelStyle()"
         class="calc-panel bg-white rounded-2xl shadow-2xl border border-gray-200 select-none">

        {{-- Draggable header --}}
        <div @mousedown="onDown($event)" @touchstart="onDown($event)"
             class="flex items-center justify-between px-4 py-2.5 bg-navy-600 text-white cursor-move">
            <span class="text-sm font-semibold flex items-center gap-2"><i class="fa fa-calculator text-xs opacity-80"></i>Calculator</span>
            <div class="flex items-center gap-1">
                <button type="button" @click="deg=!deg" x-text="deg?'DEG':'RAD'"
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-white/15 hover:bg-white/25"></button>
                <button type="button" @click="sci=!sci" title="Scientific"
                        class="w-6 h-6 rounded hover:bg-white/20 flex items-center justify-center" :class="sci ? 'bg-white/20':''">
                    <i class="fa fa-flask text-xs"></i>
                </button>
                <button type="button" @click="open=false" class="w-6 h-6 rounded hover:bg-white/20 flex items-center justify-center">
                    <i class="fa fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        {{-- Display --}}
        <div class="px-4 pt-3 pb-2 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center justify-between h-4 mb-1">
                <span class="text-[10px] font-mono text-gray-400" x-show="memory!==0" title="Memory">M</span>
                <span class="text-[11px] text-red-500 font-medium truncate" x-text="error"></span>
            </div>
            <input type="text" x-model="expr" x-ref="disp" inputmode="text" spellcheck="false" autocomplete="off"
                   @keydown.enter.prevent="equals()" @keydown.escape.prevent="open=false"
                   placeholder="0"
                   class="w-full bg-transparent text-right text-lg font-mono text-gray-900 outline-none placeholder-gray-300">
            <div class="text-right text-sm text-gray-400 font-mono h-5 truncate" x-text="expr.trim() && preview()!=='' ? '= '+preview() : ''"></div>
        </div>

        {{-- Scientific keypad (collapsible) --}}
        <div x-show="sci" x-transition class="grid grid-cols-5 gap-px bg-gray-100 border-b border-gray-100">
            <button type="button" @click="inv=!inv" :class="inv?'bg-navy-100 text-navy-700':'bg-white text-gray-600'" class="calc-sci">2nd</button>
            <button type="button" @click="push(inv?'asin(':'sin(')" x-text="inv?'sin⁻¹':'sin'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push(inv?'acos(':'cos(')" x-text="inv?'cos⁻¹':'cos'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push(inv?'atan(':'tan(')" x-text="inv?'tan⁻¹':'tan'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push('π')" class="calc-sci bg-white text-gray-600">π</button>

            <button type="button" @click="push(inv?'10^(':'log(')" x-html="inv?'10<sup>x</sup>':'log'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push(inv?'e^(':'ln(')" x-html="inv?'e<sup>x</sup>':'ln'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push(inv?'^2':'√(')" x-html="inv?'x<sup>2</sup>':'√'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push('^')" x-html="'x<sup>y</sup>'" class="calc-sci bg-white text-gray-600"></button>
            <button type="button" @click="push('e')" class="calc-sci bg-white text-gray-600">e</button>

            <button type="button" @click="reciprocal()" class="calc-sci bg-white text-gray-600">1/x</button>
            <button type="button" @click="push('!')" class="calc-sci bg-white text-gray-600">n!</button>
            <button type="button" @click="push('(')" class="calc-sci bg-white text-gray-600">(</button>
            <button type="button" @click="push(')')" class="calc-sci bg-white text-gray-600">)</button>
            <button type="button" @click="push('Ans')" class="calc-sci bg-white text-gray-500 text-xs">Ans</button>

            <button type="button" @click="memClear()" class="calc-sci bg-white text-gray-500 text-xs">MC</button>
            <button type="button" @click="memRecall()" class="calc-sci bg-white text-gray-500 text-xs">MR</button>
            <button type="button" @click="memAdd(1)" class="calc-sci bg-white text-gray-500 text-xs">M+</button>
            <button type="button" @click="memAdd(-1)" class="calc-sci bg-white text-gray-500 text-xs">M−</button>
            <button type="button" @click="push('%')" class="calc-sci bg-white text-gray-600">%</button>
        </div>

        {{-- Standard keypad --}}
        <div class="grid grid-cols-4 gap-px bg-gray-100">
            <button type="button" @click="clearAll()" class="calc-key text-red-500 font-semibold">AC</button>
            <button type="button" @click="backspace()" class="calc-key text-gray-500"><i class="fa fa-delete-left"></i></button>
            <button type="button" @click="negate()" class="calc-key text-gray-500">±</button>
            <button type="button" @click="push('÷')" class="calc-key text-navy-600 font-semibold">÷</button>

            <button type="button" @click="push('7')" class="calc-key">7</button>
            <button type="button" @click="push('8')" class="calc-key">8</button>
            <button type="button" @click="push('9')" class="calc-key">9</button>
            <button type="button" @click="push('×')" class="calc-key text-navy-600 font-semibold">×</button>

            <button type="button" @click="push('4')" class="calc-key">4</button>
            <button type="button" @click="push('5')" class="calc-key">5</button>
            <button type="button" @click="push('6')" class="calc-key">6</button>
            <button type="button" @click="push('−')" class="calc-key text-navy-600 font-semibold">−</button>

            <button type="button" @click="push('1')" class="calc-key">1</button>
            <button type="button" @click="push('2')" class="calc-key">2</button>
            <button type="button" @click="push('3')" class="calc-key">3</button>
            <button type="button" @click="push('+')" class="calc-key text-navy-600 font-semibold">+</button>

            <button type="button" @click="push('0')" class="calc-key col-span-2">0</button>
            <button type="button" @click="push('.')" class="calc-key">.</button>
            <button type="button" @click="equals()" class="calc-key bg-navy-600 text-white hover:bg-navy-700 font-semibold">=</button>
        </div>
    </div>
</div>

<style>
    /* Fixed, compact portrait footprint — width from a class so Alpine's :style
       binding can't wipe it; capped height keeps it off half the screen. */
    .calc-panel {
        position: fixed; right: 16px; bottom: 16px; z-index: 9001;
        width: clamp(150px, 44vw, 178px);
        max-height: 82vh; overflow: hidden auto;
    }
    .calc-key { padding:0.42rem 0; background:#fff; font-size:0.9rem; color:#111827; transition:background .1s; }
    .calc-key:hover { background:#f3f4f6; }
    .calc-key:active { background:#e5e7eb; }
    .calc-sci { padding:0.38rem 0; font-size:0.68rem; transition:background .1s; }
    .calc-sci:hover { filter:brightness(0.96); }
</style>

<script>
/* ── Expression engine: tokenizer + recursive-descent evaluator (no eval) ── */
function lekhyaCalcTokenize(s) {
    var t = [], i = 0, n = s.length;
    var isD = function (c) { return c >= '0' && c <= '9'; };
    var isA = function (c) { return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z'); };
    while (i < n) {
        var c = s[i];
        if (c === ' ') { i++; continue; }
        if (isD(c) || (c === '.' && isD(s[i + 1]))) {
            var j = i, dot = false, exp = false;
            while (j < n) {
                var d = s[j];
                if (isD(d)) j++;
                else if (d === '.' && !dot && !exp) { dot = true; j++; }
                else if ((d === 'e' || d === 'E') && !exp && j > i) { exp = true; j++; if (s[j] === '+' || s[j] === '-') j++; }
                else break;
            }
            t.push({ t: 'num', v: parseFloat(s.slice(i, j)) }); i = j; continue;
        }
        if (isA(c)) { var k = i; while (k < n && isA(s[k])) k++; t.push({ t: 'name', v: s.slice(i, k).toLowerCase() }); i = k; continue; }
        if ('+-*/^'.indexOf(c) >= 0) { t.push({ t: 'op', v: c }); i++; continue; }
        if (c === '(') { t.push({ t: 'lp' }); i++; continue; }
        if (c === ')') { t.push({ t: 'rp' }); i++; continue; }
        if (c === '!' || c === '%') { t.push({ t: 'post', v: c }); i++; continue; }
        throw new Error('Bad char “' + c + '”');
    }
    return t;
}

function lekhyaCalcEval(tokens, ctx) {
    var pos = 0;
    var peek = function () { return tokens[pos]; };
    var fact = function (x) { if (x < 0 || Math.floor(x) !== x) return NaN; if (x > 170) return Infinity; var r = 1; for (var k = 2; k <= x; k++) r *= k; return r; };
    var ang = function (x) { return ctx.deg ? x * Math.PI / 180 : x; };
    var iang = function (x) { return ctx.deg ? x * 180 / Math.PI : x; };
    var FN = {
        sqrt: Math.sqrt, cbrt: Math.cbrt, abs: Math.abs, exp: Math.exp,
        ln: Math.log, log: function (x) { return Math.log(x) / Math.LN10; }, log2: Math.log2,
        sin: function (x) { return Math.sin(ang(x)); }, cos: function (x) { return Math.cos(ang(x)); }, tan: function (x) { return Math.tan(ang(x)); },
        asin: function (x) { return iang(Math.asin(x)); }, acos: function (x) { return iang(Math.acos(x)); }, atan: function (x) { return iang(Math.atan(x)); },
        sinh: Math.sinh, cosh: Math.cosh, tanh: Math.tanh,
        floor: Math.floor, ceil: Math.ceil, round: Math.round, sign: Math.sign, fact: fact
    };
    var CO = { pi: Math.PI, e: Math.E, tau: 2 * Math.PI, ans: ctx.ans || 0, m: ctx.memory || 0, mem: ctx.memory || 0 };

    function parseExpr() {
        var v = parseTerm();
        while (peek() && peek().t === 'op' && (peek().v === '+' || peek().v === '-')) {
            var op = tokens[pos++].v; var r = parseTerm(); v = op === '+' ? v + r : v - r;
        }
        return v;
    }
    function starts(tok) { return tok && (tok.t === 'num' || tok.t === 'name' || tok.t === 'lp'); }
    function parseTerm() {
        var v = parseUnary();
        while (peek()) {
            var tok = peek();
            if (tok.t === 'op' && (tok.v === '*' || tok.v === '/')) { var op = tokens[pos++].v; var r = parseUnary(); v = op === '*' ? v * r : v / r; }
            else if (starts(tok)) { var r2 = parseUnary(); v = v * r2; }   // implicit multiply: 2π, 3(4)
            else break;
        }
        return v;
    }
    function parseUnary() {
        var tok = peek();
        if (tok && tok.t === 'op' && (tok.v === '-' || tok.v === '+')) { pos++; var v = parseUnary(); return tok.v === '-' ? -v : v; }
        return parsePower();
    }
    function parsePower() {
        var b = parsePostfix();
        if (peek() && peek().t === 'op' && peek().v === '^') { pos++; var e = parseUnary(); return Math.pow(b, e); }
        return b;
    }
    function parsePostfix() {
        var v = parsePrimary();
        while (peek() && peek().t === 'post') { var p = tokens[pos++].v; v = p === '!' ? fact(v) : v / 100; }
        return v;
    }
    function parsePrimary() {
        var tok = tokens[pos++];
        if (!tok) throw new Error('Incomplete');
        if (tok.t === 'num') return tok.v;
        if (tok.t === 'lp') { var v = parseExpr(); if (!peek() || peek().t !== 'rp') throw new Error('Missing )'); pos++; return v; }
        if (tok.t === 'name') {
            if (FN[tok.v]) {
                var arg;
                if (peek() && peek().t === 'lp') { pos++; arg = parseExpr(); if (!peek() || peek().t !== 'rp') throw new Error('Missing )'); pos++; }
                else arg = parsePower();
                return FN[tok.v](arg);
            }
            if (tok.v in CO) return CO[tok.v];
            throw new Error('Unknown “' + tok.v + '”');
        }
        throw new Error('Syntax');
    }

    var out = parseExpr();
    if (pos < tokens.length) throw new Error('Syntax');
    return out;
}

document.addEventListener('alpine:init', function () {
    Alpine.data('lekhyaCalc', function () {
        return {
            open: false, sci: false, inv: false, deg: true,
            expr: '', error: '', ans: 0, memory: 0, justEval: false,
            pos: { x: null, y: null }, drag: { on: false, ox: 0, oy: 0 },

            toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.disp && this.$refs.disp.focus()); },

            _map(str) {
                return String(str).replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-')
                    .replace(/π/g, 'pi').replace(/√/g, 'sqrt').replace(/Ans/g, 'ans');
            },
            tryEval(str) {
                try {
                    var toks = lekhyaCalcTokenize(this._map(str));
                    if (!toks.length) return { ok: false, error: '' };
                    var v = lekhyaCalcEval(toks, { deg: this.deg, ans: this.ans, memory: this.memory });
                    if (typeof v !== 'number' || isNaN(v)) return { ok: false, error: 'Math error' };
                    if (!isFinite(v)) return { ok: false, error: '∞' };
                    return { ok: true, value: v };
                } catch (e) { return { ok: false, error: e.message }; }
            },
            fmt(v) { if (!isFinite(v)) return String(v); return String(parseFloat(v.toPrecision(12))); },
            preview() { if (!this.expr.trim()) return ''; var r = this.tryEval(this.expr); return r.ok ? this.fmt(r.value) : ''; },

            push(s) {
                if (this.justEval) { this.justEval = false; if (!/^[+\-×÷^!%)]/.test(s)) this.expr = ''; }
                this.error = '';
                this.expr += s;
                this.$nextTick(() => this.$refs.disp && this.$refs.disp.focus());
            },
            equals() {
                var r = this.tryEval(this.expr);
                if (r.ok) { this.ans = r.value; this.expr = this.fmt(r.value); this.error = ''; this.justEval = true; }
                else if (this.expr.trim()) { this.error = r.error || 'Error'; }
            },
            clearAll() { this.expr = ''; this.error = ''; this.justEval = false; },
            backspace() { this.justEval = false; this.error = ''; this.expr = this.expr.replace(/([a-zA-Z]+\(|√\(|.)$/, ''); },
            negate() {
                var e = this.expr.trim(); this.justEval = false;
                if (!e) { this.expr = '-'; return; }
                this.expr = (e.startsWith('-(') && e.endsWith(')')) ? e.slice(2, -1) : '-(' + e + ')';
            },
            reciprocal() {
                var e = this.expr.trim(); if (!e) { this.expr = '1/'; return; }
                this.expr = '1/(' + e + ')'; this.justEval = false;
            },
            _val() { var r = this.tryEval(this.expr); return r.ok ? r.value : this.ans; },
            memClear() { this.memory = 0; },
            memRecall() { this.push(this.fmt(this.memory)); },
            memAdd(sign) { this.memory += sign * this._val(); },

            panelStyle() { return this.pos.x === null ? '' : ('left:' + this.pos.x + 'px;top:' + this.pos.y + 'px;right:auto;bottom:auto;'); },
            onDown(e) {
                if (e.target.closest('button')) return;
                var p = e.touches ? e.touches[0] : e;
                var rect = this.$refs.panel.getBoundingClientRect();
                this.pos.x = rect.left; this.pos.y = rect.top;
                this.drag.on = true; this.drag.ox = p.clientX - rect.left; this.drag.oy = p.clientY - rect.top;
                if (e.cancelable) e.preventDefault();
            },
            onMove(e) {
                if (!this.drag.on) return;
                var p = e.touches ? e.touches[0] : e;
                this.pos.x = Math.min(Math.max(0, p.clientX - this.drag.ox), window.innerWidth - 60);
                this.pos.y = Math.min(Math.max(0, p.clientY - this.drag.oy), window.innerHeight - 40);
            },
            onUp() { if (this.drag.on) { this.drag.on = false; this.save(); } },
            save() { try { localStorage.setItem('lekhya_calc', JSON.stringify({ open: this.open, memory: this.memory, deg: this.deg, sci: this.sci, pos: this.pos })); } catch (e) {} },

            init() {
                try {
                    var s = JSON.parse(localStorage.getItem('lekhya_calc') || '{}');
                    if (s.open) this.open = true;
                    if (s.sci) this.sci = true;
                    if (s.deg === false) this.deg = false;
                    if (typeof s.memory === 'number') this.memory = s.memory;
                    if (s.pos && typeof s.pos.x === 'number') this.pos = s.pos;
                } catch (e) {}
                var self = this;
                window.addEventListener('mousemove', function (e) { self.onMove(e); });
                window.addEventListener('mouseup', function () { self.onUp(); });
                window.addEventListener('touchmove', function (e) { self.onMove(e); }, { passive: false });
                window.addEventListener('touchend', function () { self.onUp(); });
                window.addEventListener('keydown', function (e) {
                    if (e.altKey && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); self.toggle(); }
                });
                this.$watch('open', function () { self.save(); });
                this.$watch('deg', function () { self.save(); });
                this.$watch('sci', function () { self.save(); });
                this.$watch('memory', function () { self.save(); });
            }
        };
    });
});
</script>
