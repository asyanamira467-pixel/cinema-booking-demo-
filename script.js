const $ = (s)=>document.querySelector(s);
const $$ = (s)=>document.querySelectorAll(s);

// Hero slideshow
(function(){
  const slides = $$('#heroSlides .hero-slide');
  if (!slides.length) return;
  let idx = 0;
  setInterval(()=>{
    slides.forEach((el,i)=>el.classList.toggle('is-active', i===idx));
    idx = (idx + 1) % slides.length;
  }, 4200);
})();

// Trailer button — buka modal YouTube (FIX #12)
(function(){
  const btn = $('#btnTrailer');
  if(!btn) return;
  btn.addEventListener('click', ()=>{
    const m = document.getElementById('trailerModal');
    if(m){
      m.classList.add('is-open');
      m.setAttribute('aria-hidden','false');
    }
  });
})();

// Modal
(function(){
  const openBtns = $$('[data-modal-open]');
  const closeBtns = $$('[data-modal-close]');

  function openModal(id){
    const m = document.getElementById(id);
    if(!m) return;
    m.classList.add('is-open');
    m.setAttribute('aria-hidden','false');
  }

  function closeModal(id){
    const m = document.getElementById(id);
    if(!m) return;
    // Hentikan iframe YouTube saat modal ditutup
    const iframe = m.querySelector('iframe');
    if(iframe){ const src = iframe.src; iframe.src=''; iframe.src=src; }
    m.classList.remove('is-open');
    m.setAttribute('aria-hidden','true');
  }

  openBtns.forEach(b=>{
    b.addEventListener('click', ()=>openModal(b.getAttribute('data-modal-open')));
  });
  closeBtns.forEach(b=>{
    b.addEventListener('click', ()=>closeModal(b.getAttribute('data-modal-close')));
  });

  $$('body .modal').forEach(m=>{
    m.addEventListener('click', (e)=>{
      if(e.target === m) {
        const iframe = m.querySelector('iframe');
        if(iframe){ const src = iframe.src; iframe.src=''; iframe.src=src; }
        m.classList.remove('is-open');
        m.setAttribute('aria-hidden','true');
      }
    });
  });

  document.addEventListener('keydown',(e)=>{
    if(e.key !== 'Escape') return;
    $$('body .modal.is-open').forEach(m=>{
      const iframe = m.querySelector('iframe');
      if(iframe){ const src = iframe.src; iframe.src=''; iframe.src=src; }
      m.classList.remove('is-open');
      m.setAttribute('aria-hidden','true');
    });
  });
})();

function showToast(msg){
  const t = $('#toast');
  if(!t) return;
  t.textContent = msg;
  t.style.display = 'block';
  setTimeout(()=>{ t.style.display='none'; }, 2600);
}

// Schedule sidebar
(function(){
  var btns = document.querySelectorAll('.sched-date-btn');
  if(!btns.length) return;
  btns.forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var targetId = btn.getAttribute('data-target');
      var target = document.getElementById(targetId);
      if(target) target.scrollIntoView({ behavior:'smooth', block:'start' });
      btns.forEach(function(b){ b.classList.remove('is-active'); });
      btn.classList.add('is-active');
    });
  });
  var groups = document.querySelectorAll('.sched-group');
  if(!groups.length) return;
  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        var id = entry.target.id;
        btns.forEach(function(b){
          b.classList.toggle('is-active', b.getAttribute('data-target') === id);
        });
      }
    });
  }, { rootMargin: '-20% 0px -60% 0px' });
  groups.forEach(function(g){ observer.observe(g); });
})();

// ===== SEARCH AUTOCOMPLETE (FIX #3: highlight regex diperbaiki) =====
(function(){
  const searchInput = document.getElementById('searchInput');
  const searchDropdown = document.getElementById('searchDropdown');
  if(!searchInput || !searchDropdown) return;

  let searchTimeout;
  let lastQuery = '';

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // FIX #3: escape query sebelum buat regex, highlight di teks plain (sebelum escapeHtml)
  function highlightMatch(text, query) {
    if (!query) return escapeHtml(text);
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp('(' + escaped + ')', 'gi');
    // Split teks asli (plain) lalu escape tiap bagian — tidak ada double-escape
    return text.replace(regex, '\x00$1\x00').split('\x00').map((part, i) =>
      i % 2 === 1
        ? '<mark style="background:rgba(230,57,70,.35);color:inherit;border-radius:2px;padding:0 1px">' + escapeHtml(part) + '</mark>'
        : escapeHtml(part)
    ).join('');
  }

  function doSearch(query) {
    if (query.length < 2) {
      searchDropdown.style.display = 'none';
      return;
    }
    if (query === lastQuery) return;
    lastQuery = query;

    fetch('api_search.php?q=' + encodeURIComponent(query))
      .then(r => {
        if (!r.ok) throw new Error('Network error');
        return r.json();
      })
      .then(results => {
        if (!results || !results.length) {
          searchDropdown.innerHTML = '<div style="padding:12px 16px;color:var(--muted);font-size:.88rem;">Tidak ada film ditemukan</div>';
          searchDropdown.style.display = 'block';
          return;
        }

        searchDropdown.innerHTML = results.map(film => {
          const harga = parseInt(film.harga_terendah || 0);
          const hargaStr = harga > 0 ? 'Rp ' + harga.toLocaleString('id-ID') : 'Lihat jadwal';
          const judulHL = highlightMatch(film.judul, query);
          return `<a href="index.php?page=movie&film_id=${encodeURIComponent(film.film_id)}" class="search-dropdown-item">
            <div class="search-dropdown-item-poster">
              <img src="assets/images/${encodeURIComponent(film.poster)}" alt="${escapeHtml(film.judul)}" onerror="this.style.display='none'">
            </div>
            <div class="search-dropdown-item-info">
              <div class="search-dropdown-item-title">${judulHL}</div>
              <div class="search-dropdown-item-price">${escapeHtml(hargaStr)}</div>
            </div>
          </a>`;
        }).join('');

        searchDropdown.style.display = 'block';
      })
      .catch(() => {
        searchDropdown.style.display = 'none';
      });
  }

  searchInput.addEventListener('input', function(){
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    if (query.length < 2) {
      searchDropdown.style.display = 'none';
      lastQuery = '';
      return;
    }
    searchTimeout = setTimeout(() => doSearch(query), 280);
  });

  searchInput.addEventListener('blur', function(){
    setTimeout(() => { searchDropdown.style.display = 'none'; }, 220);
  });

  searchInput.addEventListener('focus', function(){
    const query = this.value.trim();
    if (query.length >= 2) {
      searchDropdown.style.display = 'block';
    }
  });

  // Navigasi keyboard di dropdown
  searchInput.addEventListener('keydown', function(e){
    const items = searchDropdown.querySelectorAll('.search-dropdown-item');
    if (!items.length) return;
    const active = searchDropdown.querySelector('.search-dropdown-item.is-focused');
    let idx = Array.from(items).indexOf(active);
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      idx = (idx + 1) % items.length;
      items.forEach(i=>i.classList.remove('is-focused'));
      items[idx].classList.add('is-focused');
      items[idx].scrollIntoView({ block:'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      idx = (idx - 1 + items.length) % items.length;
      items.forEach(i=>i.classList.remove('is-focused'));
      items[idx].classList.add('is-focused');
      items[idx].scrollIntoView({ block:'nearest' });
    } else if (e.key === 'Enter' && active) {
      e.preventDefault();
      active.click();
    }
  });
})();

// ===== HAMBURGER MOBILE NAV =====
(function(){
  var btn = document.getElementById('navHamburger');
  var drawer = document.getElementById('navDrawer');
  if(!btn || !drawer) return;
  btn.addEventListener('click', function(){
    var open = drawer.classList.toggle('is-open');
    btn.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  drawer.querySelectorAll('a,.btn').forEach(function(el){
    el.addEventListener('click', function(){
      drawer.classList.remove('is-open');
      btn.classList.remove('is-open');
    });
  });
})();

// FIX #13: seat grid mobile — pastikan ada horizontal scroll wrapper
(function(){
  var wrap = document.querySelector('.seat-grid-wrap');
  if(!wrap) return;
  wrap.style.overflowX = 'auto';
  wrap.style.webkitOverflowScrolling = 'touch';
})();

(function(){
    var params = new URLSearchParams(window.location.search);
    var err = params.get('err');
    var messages = {
      'login_empty': 'Username dan password tidak boleh kosong.',
      'login_wrong': 'Username atau password salah.'
    };
    if (messages[err]) {
      var box = document.getElementById('loginErrBox');
      var modal = document.getElementById('loginModal');
      if (box) { box.textContent = messages[err]; box.style.display = 'block'; }
      if (modal) { modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); }
      // Bersihkan ?err= dari URL tanpa reload
      var url = new URL(window.location.href);
      url.searchParams.delete('err');
      history.replaceState(null, '', url.toString());
    }
  })();

(function(){
  var form = document.querySelector('#loginModal form');
  if (!form) return;
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var box = document.getElementById('loginErrBox');
    var data = new FormData(form);
    fetch('process.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: data
    })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        window.location.reload();
      } else {
        if (box) { box.textContent = res.msg; box.style.display = 'block'; }
      }
    })
    .catch(() => {
      if (box) { box.textContent = 'Terjadi kesalahan, coba lagi.'; box.style.display = 'block'; }
    });
  });
})();