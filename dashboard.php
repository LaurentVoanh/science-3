<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Discovery Engine</title>
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
.page-title{font-family:var(--display);font-size:2rem;color:var(--bright);margin-bottom:32px}

/* KPI ROW */
.kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:32px}
.kpi{background:var(--s1);border:1px solid var(--border2);border-radius:10px;padding:20px;
  display:flex;flex-direction:column;gap:6px}
.kpi-val{font-family:var(--display);font-size:2.2rem;font-weight:700}
.kpi-lbl{font-size:11px;color:var(--dim);letter-spacing:.08em}
.kpi-sub{font-size:11px;color:var(--dim)}

/* 2-col */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.card{background:var(--s1);border:1px solid var(--border);border-radius:10px;padding:20px}
.card-title{font-family:var(--display);font-size:.95rem;color:var(--bright);margin-bottom:16px}
.list-item{display:flex;justify-content:space-between;align-items:center;
  padding:9px 0;border-bottom:1px solid var(--border)}
.list-item:last-child{border-bottom:none}
.li-label{color:var(--text)}
.li-val{font-family:var(--display);font-weight:700}
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.bar-label{width:130px;font-size:11px;color:var(--dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-outer{flex:1;height:6px;background:var(--border);border-radius:3px}
.bar-inner{height:100%;border-radius:3px;background:var(--cyan);transition:width .5s}
.bar-count{font-size:11px;color:var(--dim);width:40px;text-align:right}

/* TIMELINE */
.timeline{display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto}
.tl-item{display:flex;gap:12px;align-items:flex-start}
.tl-dot{width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0}
.tl-discovery{background:var(--green)}
.tl-partial{background:var(--gold)}
.tl-text{flex:1}
.tl-target{color:var(--bright);font-size:12px}
.tl-meta{color:var(--dim);font-size:11px;margin-top:2px}

/* APIS TABLE */
.apis-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
.api-item{background:var(--s2);border:1px solid var(--border);border-radius:8px;padding:12px;text-align:center}
.api-name{font-size:11px;color:var(--cyan);margin-bottom:4px}
.api-status{font-size:10px;color:var(--green)}
</style>
</head>
<body>
<nav>
  <div class="logo">🔬 <span>Discovery</span> Engine</div>
  <div class="nav-links">
    <a href="index.php">Accueil</a>
    <a href="research.php">Recherche</a>
    <a href="discoveries.php">Découvertes</a>
    <a href="dashboard.php" class="active">Dashboard</a>
  </div>
</nav>

<div class="container">
  <h1 class="page-title">📊 Dashboard Analytique</h1>

  <div class="kpi-row">
    <div class="kpi">
      <div class="kpi-val" id="k-total" style="color:var(--cyan)">–</div>
      <div class="kpi-lbl">Total découvertes</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" id="k-high" style="color:var(--green)">–</div>
      <div class="kpi-lbl">Score ≥ 70%</div>
      <div class="kpi-sub">Haute nouveauté</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" id="k-novelty" style="color:var(--gold)">–%</div>
      <div class="kpi-lbl">Nouveauté moyenne</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" id="k-confidence" style="color:var(--text)">–%</div>
      <div class="kpi-lbl">Confiance moyenne</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" style="color:var(--cyan)">14</div>
      <div class="kpi-lbl">APIs actives</div>
      <div class="kpi-sub">avec vrais abstracts</div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">📈 Par domaine</div>
      <div id="domain-bars"></div>
    </div>
    <div class="card">
      <div class="card-title">⚡ Dernières découvertes</div>
      <div class="timeline" id="timeline"></div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">🎯 Distribution des verdicts</div>
      <div id="verdict-list"></div>
    </div>
    <div class="card">
      <div class="card-title">🔄 Qualité des itérations</div>
      <div id="iter-stats"></div>
    </div>
  </div>

  <div class="card" style="margin-top:20px">
    <div class="card-title">🌐 APIs scientifiques actives</div>
    <div class="apis-grid">
      ${['PubMed','EuropePMC','OpenAlex','CrossRef','ArXiv','SemanticScholar','UniProt','ClinVar','KEGG','DisGeNET','Zenodo','CORE','Wikidata','INSPIRE-HEP'].map(a=>`
      <div class="api-item">
        <div class="api-name">${a}</div>
        <div class="api-status">● Actif</div>
      </div>`).join('')}
    </div>
  </div>
</div>

<script>
async function loadDashboard() {
  const [stats, discs] = await Promise.all([
    fetch('engine.php?action=stats').then(r=>r.json()),
    fetch('engine.php?action=list_discoveries&limit=200').then(r=>r.json())
  ]);

  if (stats.ok) {
    document.getElementById('k-total').textContent = stats.total;
    document.getElementById('k-high').textContent = stats.high_novelty;
    document.getElementById('k-novelty').textContent = Math.round((stats.avg_novelty||0)*100)+'%';
    document.getElementById('k-confidence').textContent = Math.round((stats.avg_confidence||0)*100)+'%';
  }

  if (discs.ok && discs.data) {
    const data = discs.data;

    // Domains
    const domainCounts = {};
    data.forEach(d => { domainCounts[d.domain||'general'] = (domainCounts[d.domain||'general']||0)+1 });
    const maxD = Math.max(...Object.values(domainCounts));
    document.getElementById('domain-bars').innerHTML = Object.entries(domainCounts)
      .sort((a,b)=>b[1]-a[1]).slice(0,8).map(([dom,cnt])=>`
      <div class="bar-row">
        <div class="bar-label">${dom}</div>
        <div class="bar-outer"><div class="bar-inner" style="width:${cnt/maxD*100}%"></div></div>
        <div class="bar-count">${cnt}</div>
      </div>`).join('');

    // Timeline
    document.getElementById('timeline').innerHTML = data.slice(0,12).map(d=>`
      <div class="tl-item">
        <div class="tl-dot ${d.final_verdict==='discovery'?'tl-discovery':'tl-partial'}"></div>
        <div class="tl-text">
          <div class="tl-target">${esc(d.target||'')}</div>
          <div class="tl-meta">${d.domain||''} · Score ${Math.round(((d.novelty_score||0)*50+(d.validation_score||0)*50))}% · ${(d.created_at||'').split(' ')[0]}</div>
        </div>
      </div>`).join('');

    // Verdicts
    const verdicts = {discovery:0, partial:0, pending:0};
    data.forEach(d => { verdicts[d.final_verdict||'pending'] = (verdicts[d.final_verdict||'pending']||0)+1 });
    document.getElementById('verdict-list').innerHTML = [
      ['discovery','🏆 Validées',verdicts.discovery,'var(--green)'],
      ['partial','📝 Partielles',verdicts.partial,'var(--gold)'],
      ['pending','⏳ En cours',verdicts.pending,'var(--dim)'],
    ].map(([k,lbl,cnt,c])=>`
      <div class="list-item">
        <span class="li-label">${lbl}</span>
        <span class="li-val" style="color:${c}">${cnt}</span>
      </div>`).join('');

    // Iteration stats
    const iters = data.map(d=>d.iterations||0);
    const avgIter = iters.length ? (iters.reduce((a,b)=>a+b,0)/iters.length).toFixed(1) : 0;
    const maxIter = Math.max(...iters, 0);
    document.getElementById('iter-stats').innerHTML = [
      ['Itérations moyennes',avgIter,'var(--cyan)'],
      ['Maximum itérations',maxIter,'var(--text)'],
      ['Sources consultées (avg)',data.length ? (data.reduce((a,d)=>a+JSON.parse(d.sources_consulted||'[]').length,0)/data.length).toFixed(1) : 0,'var(--green)'],
    ].map(([lbl,val,c])=>`
      <div class="list-item">
        <span class="li-label">${lbl}</span>
        <span class="li-val" style="color:${c}">${val}</span>
      </div>`).join('');
  }
}

function esc(t){const d=document.createElement('div');d.textContent=String(t||'');return d.innerHTML}
loadDashboard();
setInterval(loadDashboard, 30000);
</script>
</body>
</html>
