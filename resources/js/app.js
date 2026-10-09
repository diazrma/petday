import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/**
 * Modal do PetDay no lugar do confirm()/alert() do navegador.
 * petConfirm(msg, opts) e petAlert(msg, opts) devolvem uma Promise; formulários usam data-confirm="…".
 */
function petDialog({ message, title = 'Tem certeza?', confirmText = 'Confirmar', cancelText = 'Cancelar', icon = '🐾', danger = false, alertOnly = false }) {
    return new Promise((resolve) => {
        const root = document.createElement('div');
        root.className = 'fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-4';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.innerHTML = `
            <div data-backdrop class="absolute inset-0 bg-night/60 opacity-0 backdrop-blur-sm transition-opacity duration-200"></div>
            <div data-card class="relative w-full max-w-sm translate-y-8 rounded-t-[2rem] bg-surface p-6 text-center text-ink opacity-0 shadow-2xl transition duration-200 sm:rounded-[2rem]">
                <div class="mx-auto mb-3 flex size-16 items-center justify-center rounded-full text-3xl ${danger ? 'bg-rose-50' : 'bg-brand-50'}"></div>
                <h2 class="font-display text-2xl font-semibold"></h2>
                <p class="mt-1.5 text-sm text-stone-500"></p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
                    ${alertOnly ? '' : '<button type="button" data-cancel class="btn-ghost flex-1 !py-3"></button>'}
                    <button type="button" data-ok class="${danger ? 'btn-danger' : 'btn-primary'} flex-1 !py-3"></button>
                </div>
            </div>`;
        // textos via textContent (nada de HTML vindo de fora)
        root.querySelector('[data-card] > div').textContent = icon;
        root.querySelector('h2').textContent = title;
        root.querySelector('p').textContent = message || '';
        root.querySelector('[data-ok]').textContent = confirmText;
        if (!alertOnly) root.querySelector('[data-cancel]').textContent = cancelText;

        const previous = document.activeElement;
        document.body.appendChild(root);
        document.body.dataset.modal = '1';
        requestAnimationFrame(() => {
            root.querySelector('[data-backdrop]').classList.remove('opacity-0');
            root.querySelector('[data-card]').classList.remove('opacity-0', 'translate-y-8');
            root.querySelector('[data-ok]').focus();
        });

        const close = (result) => {
            document.removeEventListener('keydown', onKey, true);
            root.querySelector('[data-backdrop]').classList.add('opacity-0');
            root.querySelector('[data-card]').classList.add('opacity-0', 'translate-y-8');
            setTimeout(() => {
                root.remove();
                delete document.body.dataset.modal;
                previous?.focus?.();
            }, 200);
            resolve(result);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(alertOnly); }
        };
        document.addEventListener('keydown', onKey, true);
        root.querySelector('[data-ok]').addEventListener('click', () => close(true));
        root.querySelector('[data-cancel]')?.addEventListener('click', () => close(false));
        root.querySelector('[data-backdrop]').addEventListener('click', () => close(alertOnly));
    });
}
window.petConfirm = (message, opts = {}) => petDialog({ danger: true, icon: '🗑️', ...opts, message });
window.petAlert = (message, opts = {}) => petDialog({ title: 'Ops!', icon: '🙀', confirmText: 'Entendi', ...opts, message, alertOnly: true });

// Formulários com data-confirm pedem confirmação no modal antes de enviar
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    const ok = await window.petConfirm(form.dataset.confirm, {
        title: form.dataset.confirmTitle || 'Tem certeza?',
        confirmText: form.dataset.confirmButton || 'Confirmar',
        cancelText: form.dataset.cancelButton || 'Voltar',
        icon: form.dataset.confirmIcon || '🗑️',
    });
    if (ok) form.submit();
}, true);


/**
 * Botão "patinha" — substitui o like. Faz toggle via AJAX com animação de patinhas voando.
 */
Alpine.data('paw', (url, pawed, count) => ({
    pawed,
    count,
    busy: false,
    pop: false,
    async toggle(event) {
        if (this.busy) return;
        this.busy = true;
        // otimista
        this.pawed = !this.pawed;
        this.count += this.pawed ? 1 : -1;
        if (this.pawed) this.burst(event.currentTarget);
        try {
            const { data } = await window.axios.post(url);
            this.pawed = data.pawed;
            this.count = data.count;
        } catch (e) {
            this.pawed = !this.pawed;
            this.count += this.pawed ? 1 : -1;
        } finally {
            this.busy = false;
        }
    },
    burst(el) {
        this.pop = false;
        requestAnimationFrame(() => (this.pop = true));
        for (let i = 0; i < 6; i++) {
            const s = document.createElement('span');
            s.textContent = '🐾';
            s.className = 'paw-particle text-sm';
            s.style.left = '8px';
            s.style.top = '0';
            s.style.setProperty('--dx', `${(Math.random() - 0.5) * 80}px`);
            s.style.setProperty('--r', `${(Math.random() - 0.5) * 90}deg`);
            s.style.animationDelay = `${i * 40}ms`;
            el.appendChild(s);
            setTimeout(() => s.remove(), 1100);
        }
    },
}));

/** Duplo toque na foto também dá patinha. */
Alpine.data('doubleTapPaw', () => ({
    showBig: false,
    tap() {
        const btn = this.$root.querySelector('[data-paw-btn]');
        if (btn && btn.dataset.pawed !== 'true') btn.click();
        this.showBig = true;
        setTimeout(() => (this.showBig = false), 800);
    },
}));

/**
 * Visualizador de rastros: trilha de patinhas como progresso, segurar para "farejar" (pausa),
 * toque duplo no meio dá petisco, reações de pet e recados públicos.
 */
Alpine.data('rastroViewer', (cfg) => ({
    ...cfg,
    index: 0,
    progress: 0,
    duration: 6000,
    holding: false,
    drawer: false,
    draft: '',
    sending: false,
    // arrastar
    dragging: false,
    dragX: 0,
    dragY: 0,
    leaving: false,
    pointer: null,
    lastFrame: 0,
    holdTimer: null,
    lastTap: 0,
    tapTimer: null,
    tick: 0,
    get cur() { return this.items[this.index]; },
    /** Até 2 recados por vez; se houver mais, a janela vai andando para mostrar todos. */
    visibleComments() {
        const all = this.cur.comments;
        if (all.length <= 2) return all;
        const start = this.tick % all.length;
        return [0, 1].map((k) => all[(start + k) % all.length]);
    },
    init() {
        setInterval(() => {
            if (!this.holding && !this.drawer && !this.dragging) this.tick++;
        }, 2200);
        const loop = (t) => {
            const dt = this.lastFrame ? Math.min(t - this.lastFrame, 100) : 0;
            this.lastFrame = t;
            if (!this.holding && !this.drawer && !this.dragging && !this.leaving && !document.hidden && !document.body.dataset.modal) {
                this.progress += dt / this.duration;
                if (this.progress >= 1) this.next();
            }
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);
    },
    width() { return this.$refs.stage?.offsetWidth || window.innerWidth; },
    /** Distância (em telas) de cada polaroid até o centro, considerando o dedo. */
    offset(i) { return i - this.index - this.dragX / this.width(); },
    trackStyle() {
        const y = Math.max(0, this.dragY);
        return `transform: translate3d(calc(${-this.index * 100}% + ${this.dragX}px), ${y}px, 0) scale(${1 - Math.min(y / 1500, 0.15)});`
            + `transition: ${this.dragging ? 'none' : 'transform .55s cubic-bezier(.22,.9,.25,1)'}`;
    },
    /** Polaroid que sai/entra encolhe e gira um pouco: a "passada". */
    slideStyle(i) {
        const o = Math.max(-1, Math.min(1, this.offset(i)));
        const a = Math.abs(o);
        return `transform: translateX(${o * 12}%) scale(${1 - a * 0.14}) rotate(${o * 7}deg); border-radius: ${a * 28}px;`
            + `transition: ${this.dragging ? 'none' : 'transform .55s cubic-bezier(.22,.9,.25,1), border-radius .55s'}`;
    },
    fill(i) { return i < this.index ? 1 : i === this.index ? Math.min(this.progress, 1) : 0; },
    go(i) { this.index = Math.max(0, Math.min(this.items.length - 1, i)); this.progress = 0; this.tick = 0; },
    next() {
        if (this.index < this.items.length - 1) return this.go(this.index + 1);
        this.leave(this.nextUrl || this.closeUrl);
    },
    prev() { this.go(this.index - 1); },
    leave(url) {
        if (this.leaving) return;
        this.progress = 1;
        this.leaving = true;
        setTimeout(() => (window.location = url), 280);
    },
    down(e) {
        this.pointer = { x: e.clientX, y: e.clientY, t: Date.now(), axis: null };
        e.currentTarget.setPointerCapture?.(e.pointerId);
        clearTimeout(this.holdTimer);
        this.holdTimer = setTimeout(() => { if (!this.dragging) this.holding = true; }, 220);
    },
    move(e) {
        if (!this.pointer) return;
        const dx = e.clientX - this.pointer.x;
        const dy = e.clientY - this.pointer.y;
        if (!this.pointer.axis && Math.hypot(dx, dy) > 10) {
            this.pointer.axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
            this.dragging = true;
            this.holding = false;
            clearTimeout(this.holdTimer);
        }
        if (this.pointer.axis === 'x') {
            // resistência nas pontas
            const edge = (dx > 0 && this.index === 0) || (dx < 0 && this.index === this.items.length - 1 && !this.nextUrl);
            this.dragX = edge ? dx * 0.3 : dx;
        } else if (this.pointer.axis === 'y') {
            this.dragY = dy;
        }
    },
    up(e) {
        clearTimeout(this.holdTimer);
        const p = this.pointer;
        this.pointer = null;
        if (!p) return;

        if (this.dragging) {
            const fast = Date.now() - p.t < 250;
            if (p.axis === 'x') {
                const limit = this.width() * (fast ? 0.08 : 0.25);
                if (this.dragX < -limit) this.next();
                else if (this.dragX > limit) this.prev();
            } else if (p.axis === 'y' && this.dragY > 120) {
                this.dragging = false;
                return this.leave(this.closeUrl);
            }
            this.dragging = false;
            this.dragX = 0;
            this.dragY = 0;
            return;
        }
        if (this.holding) { this.holding = false; return; }

        const r = e.currentTarget.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        if (x < 0.3) return this.prev();
        if (x > 0.7) return this.next();
        // Meio: toque duplo = petisco; toque simples avança
        const now = Date.now();
        if (now - this.lastTap < 300) {
            clearTimeout(this.tapTimer);
            this.lastTap = 0;
            if (this.cur.mine !== 'treat') this.react('treat');
            else this.rain('🦴');
            return;
        }
        this.lastTap = now;
        this.tapTimer = setTimeout(() => this.next(), 300);
    },
    cancel() {
        clearTimeout(this.holdTimer);
        this.pointer = null;
        this.dragging = false;
        this.holding = false;
        this.dragX = 0;
        this.dragY = 0;
    },
    async react(kind) {
        const item = this.cur;
        const before = { mine: item.mine, counts: { ...item.counts } };
        // otimista
        if (item.mine) item.counts[item.mine]--;
        item.mine = item.mine === kind ? null : kind;
        if (item.mine) { item.counts[kind]++; this.rain(this.reactions[kind].emoji); }
        try {
            const { data } = await window.axios.post(this.reactUrl.replace('__ID__', item.id), { kind });
            item.mine = data.mine;
            item.counts = data.counts;
        } catch (e) {
            Object.assign(item, before);
        }
    },
    rain(emoji) {
        const box = this.$refs.rain;
        for (let i = 0; i < 16; i++) {
            const s = document.createElement('span');
            s.textContent = emoji;
            s.className = 'rastro-rain';
            s.style.left = `${Math.random() * 92}%`;
            s.style.fontSize = `${22 + Math.random() * 26}px`;
            s.style.setProperty('--sway', `${(Math.random() - 0.5) * 120}px`);
            s.style.setProperty('--spin', `${(Math.random() - 0.5) * 120}deg`);
            s.style.animationDelay = `${Math.random() * 400}ms`;
            box.appendChild(s);
            setTimeout(() => s.remove(), 2200);
        }
    },
    openDrawer(focus = false) {
        this.drawer = true;
        this.$nextTick(() => {
            this.$refs.list.scrollTop = this.$refs.list.scrollHeight;
            if (focus) this.$refs.input.focus();
        });
    },
    async sendComment() {
        const body = this.draft.trim();
        if (!body || this.sending) return;
        this.sending = true;
        const item = this.cur;
        try {
            const { data } = await window.axios.post(this.commentUrl.replace('__ID__', item.id), { body });
            item.comments.push(data);
            this.draft = '';
            this.$nextTick(() => (this.$refs.list.scrollTop = this.$refs.list.scrollHeight));
        } catch (e) {
            window.petAlert(e.response?.data?.message || 'Não foi possível enviar o recado. Tente de novo.');
        } finally {
            this.sending = false;
        }
    },
    async removeComment(c) {
        if (!(await window.petConfirm('O recado some para todo mundo.', { title: 'Apagar recado?', confirmText: 'Apagar' }))) return;
        await window.axios.delete(c.deleteUrl);
        this.cur.comments = this.cur.comments.filter((x) => x.id !== c.id);
    },
}));

/**
 * Rolagem infinita: observa o fim da lista, busca a próxima página no servidor e
 * anexa os itens de [data-infinite-items]. O Alpine inicializa sozinho os novos elementos.
 */
Alpine.data('infiniteScroll', (nextUrl) => ({
    nextUrl,
    loading: false,
    failed: false,
    observer: null,
    init() {
        this.observer = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting)) this.load();
        }, { rootMargin: '800px 0px' });
        this.observer.observe(this.$el);
    },
    destroy() { this.observer?.disconnect(); },
    async load() {
        if (this.loading || !this.nextUrl) return;
        this.loading = true;
        this.failed = false;
        try {
            const res = await fetch(this.nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Infinite-Scroll': '1' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error(res.status);
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const target = document.querySelector('[data-infinite-items]');
            const incoming = doc.querySelector('[data-infinite-items]');
            if (target && incoming) target.append(...incoming.children);

            const next = doc.querySelector('[data-infinite-next]');
            this.nextUrl = next ? next.getAttribute('data-infinite-next') : null;
            if (!this.nextUrl) {
                this.observer.disconnect();
                this.$el.outerHTML = '<p class="py-6 text-center text-sm font-bold text-stone-400">🐾 Você chegou ao fim</p>';
                return;
            }
            this.$el.dataset.infiniteNext = this.nextUrl;
            // Se a página ainda não encheu a tela, o observer não dispara de novo sozinho
            this.observer.unobserve(this.$el);
            this.observer.observe(this.$el);
        } catch (e) {
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },
}));

/** Legenda arrastável na prévia do rastro; posição em % do centro da etiqueta. */
Alpine.data('captionDrag', () => ({
    x: 50,
    y: 50,
    moved: false,
    dragging: false,
    grab: null,
    /** Antes de mexer, usa o mesmo padrão do servidor: embaixo na foto, centro no texto. */
    pos() { return this.moved ? { x: this.x, y: this.y } : { x: 50, y: this.src ? 58 : 40 }; },
    startDrag(e) {
        const p = this.pos();
        this.x = p.x; this.y = p.y;
        const r = this.$refs.canvas.getBoundingClientRect();
        this.grab = { dx: e.clientX - (r.left + r.width * this.x / 100), dy: e.clientY - (r.top + r.height * this.y / 100) };
        this.dragging = true;
        e.currentTarget.setPointerCapture?.(e.pointerId);
    },
    drag(e) {
        if (!this.dragging) return;
        const r = this.$refs.canvas.getBoundingClientRect();
        this.x = Math.min(88, Math.max(12, ((e.clientX - this.grab.dx - r.left) / r.width) * 100));
        this.y = Math.min(92, Math.max(8, ((e.clientY - this.grab.dy - r.top) / r.height) * 100));
        this.moved = true;
    },
    endDrag() { this.dragging = false; },
    resetPos() { this.moved = false; },
}));

/** Preview de imagem para inputs de upload. */
Alpine.data('imagePreview', (initial = null) => ({
    src: initial,
    pick(e) {
        const f = e.target.files[0];
        if (f) this.src = URL.createObjectURL(f);
    },
    clear() {
        this.src = null;
        this.$refs.file.value = '';
    },
}));

window.Alpine = Alpine;
Alpine.start();
