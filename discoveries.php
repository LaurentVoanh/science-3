<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Découvertes — Discovery Engine</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#060a0f;--s1:#0b1118;--s2:#10181f;--s3:#161f28;
  --border:rgba(0,200,255,0.1);--border2:rgba(0,200,255,0.22);
  --cyan:#00c8ff;--green:#0affb0;--red:#ff3d6b;--gold:#ffd700;
  --text:#b8d4e8;--dim:#4a6a85;--bright:#e2f0ff;
  --mono:'Space Mono',monospace;--display:'Syne',sans-serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{background:var(--bg);color:var(--text);font-family:var(--mono);font-size:13px;min-height:100vh}
nav{position:fixed;top:0;width:100%;z-index:100;background:rgba(6,10,15,0.92);
  backdrop-filter:blur(12px);border-bottom:1px solid var(--border2);
  display:flex;justify-content:space-between;align-items:center;padding:12px 24px}
.logo{font-family:var(--display);font-weight:800;font-size:16px;color:var(--bright)}
.logo span{color:var(--cyan)}
.nav-links a{color:var(--dim);text-decoration:none;font-size:11px;margin-left:16px;transition:color .2s}
.nav-links a:hover,.nav-links a.active{color:var(--cyan)}

.container{max-width:1200px;margin:0 auto;padding:90px 24px 60px}
.page-title{font-family:var(--display);font-size:2rem;color:var(--bright);margin-bottom:8px}
.page-sub{color:var(--dim);margin-bottom:32px}

/* FILTERS */
.filters{display:flex;gap:10px;margin-bottom:28px;flex-wrap:wrap;align-items:center}
.filter-btn{padding:6px 16px;border-radius:20px;border:1px solid var(--border2);
  background:transparent;color:var(--dim);cursor:pointer;font-family:var(--mono);
  font-size:11px;transition:all .2s;letter-spacing:.05em}
.filter-btn.active,.filter-btn:hover{background:rgba(0,200,255,0.1);border-color:var(--cyan);color:var(--cyan)}
.filter-input{padding:7px 14px;background:var(--s2);border:1px solid var(--border2);
  color:var(--bright);font-family:var(--mono);font-size:12px;border-radius:6px;outline:none;
  margin-left:auto;width:240px}
.filter-input:focus{border-color:var(--cyan)}

/* GRID */
.disc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px}
.disc-card{background:var(--s1);border:1px solid var(--border);border-radius:10px;padding:20px;
  cursor:pointer;transition:all .25s;display:flex;flex-direction:column;gap:10px}
.disc-card:hover{border-color:var(--cyan);transform:translateY(-2px);box-shadow:0 4px 20px rgba(0,200,255,0.06)}

.dc-header{display:flex;justify-content:space-between;align-items:flex-start}
.dc-target{font-family:var(--display);font-size:1rem;color:var(--bright);flex:1;line-height:1.3}
.dc-score-badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;
  font-family:var(--display);white-space:nowrap;margin-left:10px}
.sc-hi{background:rgba(10,255,176,0.12);color:var(--green)}
.sc-me{background:rgba(255,215,0,0.12);color:var(--gold)}
.sc-lo{background:rgba(255,61,107,0.12);color:var(--red)}

.dc-hypo{color:var(--dim);font-size:11px;line-height:1.6;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.dc-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
.dc-tag{font-size:10px;padding:2px 8px;border-radius:12px;background:var(--s3);
  color:var(--dim);border:1px solid var(--border)}
.dc-bars{display:flex;gap:8px}
.dc-bar{flex:1}
.dc-bar-lbl{font-size:10px;color:var(--dim);margin-bottom:3px}
.bar-outer{height:3px;background:var(--border);border-radius:2px}
.bar-inner{height:100%;border-radius:2px;transition:width .4s}
.bar-cyan{background:var(--cyan)}
.bar-green{background:var(--green)}
.bar-gold{background:var(--gold)}

/* MODAL DISCOVERY */
.modal-overlay{position:fixed;inset:0;background:rgba(6,10,15,0.88);backdrop-filter:blur(10px);
  z-index:200;display:none;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 20px}
.modal-overlay.show{display:flex}
.modal{background:var(--s2);border:1px solid var(--border2);border-radius:12px;
  width:min(90vw,760px);padding:32px;position:relative;margin:auto}
.modal-close{position:absolute;top:16px;right:16px;background:none;border:none;
  color:var(--dim);cursor:pointer;font-size:18px;transition:color .2s}
.modal-close:hover{color:var(--red)}
.modal-title{font-family:var(--display);font-size:1.5rem;color:var(--bright);margin-bottom:6px}
.modal-domain{color:var(--cyan);font-size:11px;letter-spacing:.1em;margin-bottom:24px}
.m-section{background:var(--s3);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px}
.m-label{font-size:10px;letter-spacing:.15em;color:var(--cyan);text-transform:uppercase;margin-bottom:8px}
.m-text{color:var(--text);line-height:1.8;font-size:12px;white-space:pre-wrap}
.m-scores{display:flex;gap:12px;margin-bottom:14px}
.m-score-box{flex:1;text-align:center;background:var(--s3);border-radius:8px;padding:12px;border:1px solid var(--border)}
.m-score-val{font-family:var(--display);font-size:1.8rem;font-weight:700}
.m-score-lbl{font-size:10px;color:var(--dim);margin-top:4px}
.kw-row{display:flex;flex-wrap:wrap;gap:6px}
.kw{padding:4px 12px;background:var(--s2);border:1px solid var(--border2);border-radius:20px;font-size:11px;color:var(--dim)}
.src-row{display:flex;flex-wrap:wrap;gap:6px}
.src{padding:4px 12px;background:rgba(0,200,255,0.06);border:1px solid rgba(0,200,255,0.15);
  border-radius:20px;font-size:11px;color:var(--cyan)}
.verdict-big{display:inline-block;padding:8px 20px;border-radius:20px;font-weight:700;
  font-size:13px;margin-bottom:16px}
.v-discovery{background:rgba(10,255,176,0.15);color:var(--green);border:1px solid rgba(10,255,176,0.35)}
.v-partial{background:rgba(255,215,0,0.12);color:var(--gold);border:1px solid rgba(255,215,0,0.3)}
.v-pending{background:rgba(0,200,255,0.1);color:var(--cyan);border:1px solid rgba(0,200,255,0.25)}
.critique-section{background:var(--s3);border-left:3px solid var(--gold);border-radius:0 8px 8px 0;padding:14px;margin-bottom:14px}
.critique-item{margin-bottom:8px;color:var(--dim);line-height:1.6}
.critique-item strong{color:var(--text)}
.empty-state{text-align:center;padding:80px 20px;color:var(--dim)}
.empty-state .big{font-size:4rem;margin-bottom:16px}
</style>
</head>
<body>
<nav>
  <div class="logo">🔬 <span>Discovery</span> Engine</div>
  <div class="nav-links">
    <a href="index.php">Accueil</a>
    <a href="research.php">Recherche</a>
    <a href="discoveries.php" class="active">Découvertes</a>
    <a href="dashboard.php">Dashboard</a>
  </div>
</nav>

<div class="container">
  <h1 class="page-title">🏆 Toutes les Découvertes</h1>
  <p class="page-sub" id="disc-count">Chargement...</p>

  <div class="filters">
    <button class="filter-btn active" onclick="filter('all',this)">Toutes</button>
    <button class="filter-btn" onclick="filter('discovery',this)">🏆 Validées</button>
    <button class="filter-btn" onclick="filter('partial',this)">📝 Partielles</button>
    <button class="filter-btn" onclick="filter('auto',this)">🤖 Autonomes</button>
    <button class="filter-btn" onclick="filter('guided',this)">💬 Guidées</button>
    <input type="text" class="filter-input" id="search-input" placeholder="🔍 Rechercher..." oninput="doSearch()">
  </div>

  <div class="disc-grid" id="disc-grid">
    <div class="empty-state" style="grid-column:1/-1">
      <div class="big">⏳</div><div>Chargement...</div>
    </div>
  </div>
</div>

<!-- DETAIL MODAL -->
<div class="modal-overlay" id="detail-modal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('detail-modal').classList.remove('show')">✕</button>
    <div id="detail-content"></div>
  </div>
</div>

<script>
let allDiscs = [];
let currentFilter = 'all';
let searchQuery = '';

async function loadDiscoveries() {
  const r = await fetch('engine.php?action=list_discoveries&limit=200').then(r=>r.json());
  if (!r.ok) return;
  allDiscs = r.data || [];
  document.getElementById('disc-count').textContent = `${allDiscs.length} découvertes générées`;
  renderGrid();
}

function filter(type, btn) {
  currentFilter = type;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderGrid();
}

function doSearch() {
  searchQuery = document.getElementById('search-input').value.toLowerCase();
  renderGrid();
}

function renderGrid() {
  let data = allDiscs;
  if (currentFilter === 'discovery') data = data.filter(d=>d.final_verdict==='discovery');
  else if (currentFilter === 'partial') data = data.filter(d=>d.final_verdict==='partial');
  else if (currentFilter === 'auto') data = data.filter(d=>d.mode==='auto');
  else if (currentFilter === 'guided') data = data.filter(d=>d.mode==='guided');
  if (searchQuery) {
    data = data.filter(d=>(d.target||'').toLowerCase().includes(searchQuery) ||
      (d.hypothesis||'').toLowerCase().includes(searchQuery) ||
      (d.domain||'').toLowerCase().includes(searchQuery));
  }

  const grid = document.getElementById('disc-grid');
  if (!data.length) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="big">🔭</div><div>Aucune découverte trouvée</div></div>';
    return;
  }

  grid.innerHTML = data.map(d => {
    const score = Math.round(((d.novelty_score||0)*50 + (d.validation_score||0)*50));
    const scCls = score>=65?'sc-hi':score>=40?'sc-me':'sc-lo';
    const kws = JSON.parse(d.keywords||'[]').slice(0,4);
    const srcs = JSON.parse(d.sources_consulted||'[]').slice(0,4);
    return `<div class="disc-card" onclick="showDetail(${d.id})">
      <div class="dc-header">
        <div class="dc-target">${esc(d.target)}</div>
        <div class="dc-score-badge ${scCls}">${score}%</div>
      </div>
      <div class="dc-hypo">${esc(d.vulgarized || d.hypothesis || '')}</div>
      <div class="dc-bars">
        <div class="dc-bar">
          <div class="dc-bar-lbl">Nouveauté ${Math.round((d.novelty_score||0)*100)}%</div>
          <div class="bar-outer"><div class="bar-inner bar-cyan" style="width:${(d.novelty_score||0)*100}%"></div></div>
        </div>
        <div class="dc-bar">
          <div class="dc-bar-lbl">Validité ${Math.round((d.validation_score||0)*100)}%</div>
          <div class="bar-outer"><div class="bar-inner bar-gold" style="width:${(d.validation_score||0)*100}%"></div></div>
        </div>
      </div>
      <div class="dc-meta">
        <span class="dc-tag">${d.domain||'?'}</span>
        <span class="dc-tag">${d.mode==='guided'?'💬':'🤖'} ${d.mode}</span>
        <span class="dc-tag">iter ${d.iterations||0}</span>
        ${kws.map(k=>`<span class="dc-tag">${esc(k)}</span>`).join('')}
      </div>
    </div>`;
  }).join('');
}

async function showDetail(id) {
  const r = await fetch(`engine.php?action=get_discovery&id=${id}`).then(r=>r.json());
  if (!r.ok) return;
  const d = r.data;
  const srcs = r.sources || [];
  const critique = JSON.parse(d.critique||'{}');
  const kws = JSON.parse(d.keywords||'[]');
  const srcs_list = JSON.parse(d.sources_consulted||'[]');
  const score = Math.round(((d.novelty_score||0)*50 + (d.validation_score||0)*50));
  const vmap = {discovery:['v-discovery','🏆 DÉCOUVERTE VALIDÉE'], partial:['v-partial','📝 Résultat Partiel'], pending:['v-pending','⏳ En cours']};
  const [vcls, vlabel] = vmap[d.final_verdict||'pending'] || vmap.pending;

  document.getElementById('detail-content').innerHTML = `
    <div class="modal-title">${esc(d.target)}</div>
    <div class="modal-domain">${d.domain?.toUpperCase()||'GÉNÉRAL'} · ${d.mode==='guided'?'💬 MODE GUIDÉ':'🤖 MODE AUTONOME'} · ${(d.created_at||'').split(' ')[0]}</div>
    <span class="verdict-big ${vcls}">${vlabel} — Score: ${score}%</span>
    <div class="m-scores">
      <div class="m-score-box"><div class="m-score-val" style="color:var(--cyan)">${Math.round((d.novelty_score||0)*100)}%</div><div class="m-score-lbl">Nouveauté</div></div>
      <div class="m-score-box"><div class="m-score-val" style="color:var(--green)">${Math.round((d.confidence||0)*100)}%</div><div class="m-score-lbl">Confiance</div></div>
      <div class="m-score-box"><div class="m-score-val" style="color:var(--gold)">${Math.round((d.validation_score||0)*100)}%</div><div class="m-score-lbl">Validité</div></div>
      <div class="m-score-box"><div class="m-score-val" style="color:var(--text)">${d.iterations||0}</div><div class="m-score-lbl">Itérations</div></div>
    </div>
    ${d.question ? `<div class="m-section"><div class="m-label">Question initiale</div><div class="m-text" style="color:var(--cyan)">${esc(d.question)}</div></div>` : ''}
    <div class="m-section"><div class="m-label">Hypothèse scientifique</div><div class="m-text">${esc(d.hypothesis||'–')}</div></div>
    <div class="m-section"><div class="m-label">Explication simple</div><div class="m-text" style="color:var(--dim)">${esc(d.vulgarized||'–')}</div></div>
    <div class="m-section"><div class="m-label">Mécanisme proposé</div><div class="m-text" style="color:var(--dim)">${esc(d.mechanism||'–')}</div></div>
    ${d.actionable ? `<div class="m-section"><div class="m-label">Protocole expérimental</div><div class="m-text" style="color:var(--dim)">${esc(d.actionable)}</div></div>` : ''}
    ${critique.logical_flaws?.length ? `<div class="critique-section">
      <div class="m-label" style="color:var(--gold)">Critiques scientifiques</div>
      ${(critique.logical_flaws||[]).slice(0,3).map(f=>`<div class="critique-item"><strong>${f.severity?.toUpperCase()||''}:</strong> ${esc(f.flaw||'')} → ${esc(f.fix||'')}</div>`).join('')}
    </div>` : ''}
    <div class="m-section">
      <div class="m-label">Sources consultées (${srcs_list.length})</div>
      <div class="src-row">${srcs_list.map(s=>`<span class="src">${esc(s)}</span>`).join('')}</div>
    </div>
    ${kws.length ? `<div class="m-section"><div class="m-label">Mots-clés</div><div class="kw-row">${kws.map(k=>`<span class="kw">${esc(k)}</span>`).join('')}</div></div>` : ''}
  `;
  document.getElementById('detail-modal').classList.add('show');
}

document.getElementById('detail-modal').addEventListener('click', e => {
  if (e.target === document.getElementById('detail-modal'))
    document.getElementById('detail-modal').classList.remove('show');
});

function esc(t) { const d=document.createElement('div');d.textContent=String(t||'');return d.innerHTML }
loadDiscoveries();
</script>
</body>
</html>
