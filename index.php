<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Discovery Engine — Découvertes Scientifiques Autonomes</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #060a0f; --s1: #0b1118; --s2: #10181f; --s3: #161f28;
  --border: rgba(0,200,255,0.1); --border2: rgba(0,200,255,0.22);
  --cyan: #00c8ff; --green: #0affb0; --red: #ff3d6b; --gold: #ffd700;
  --text: #b8d4e8; --dim: #4a6a85; --bright: #e2f0ff;
  --mono: 'Space Mono', monospace; --display: 'Syne', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--mono);font-size:13px;overflow-x:hidden}
body::after{content:'';position:fixed;inset:0;pointer-events:none;
  background:radial-gradient(ellipse 80% 50% at 50% -10%,rgba(0,200,255,0.05),transparent);z-index:0}

/* NAV */
nav{position:fixed;top:0;width:100%;z-index:100;background:rgba(6,10,15,0.9);
  backdrop-filter:blur(12px);border-bottom:1px solid var(--border2);
  display:flex;justify-content:space-between;align-items:center;padding:12px 24px}
.logo{font-family:var(--display);font-weight:800;font-size:18px;color:var(--bright)}
.logo span{color:var(--cyan)}
.nav-links a{color:var(--dim);text-decoration:none;font-size:11px;margin-left:16px;
  letter-spacing:.05em;transition:color .2s}
.nav-links a:hover,.nav-links a.active{color:var(--cyan)}

/* HERO */
#hero{position:relative;z-index:1;padding:120px 24px 60px;text-align:center;max-width:900px;margin:0 auto}
.hero-label{font-size:11px;letter-spacing:.2em;color:var(--cyan);text-transform:uppercase;margin-bottom:16px}
.hero-title{font-family:var(--display);font-size:clamp(2rem,5vw,4rem);font-weight:800;
  color:var(--bright);line-height:1.1;margin-bottom:20px}
.hero-title span{color:var(--cyan)}
.hero-sub{color:var(--dim);font-size:14px;line-height:1.7;max-width:600px;margin:0 auto 40px}

/* CARDS */
.modes{display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px;margin:0 auto 60px;padding:0 24px}
.mode-card{background:var(--s1);border:1px solid var(--border2);border-radius:12px;padding:28px;
  cursor:pointer;transition:all .25s;position:relative;z-index:1}
.mode-card:hover{border-color:var(--cyan);transform:translateY(-3px);
  box-shadow:0 0 30px rgba(0,200,255,0.1)}
.mode-icon{font-size:2.5rem;margin-bottom:14px}
.mode-title{font-family:var(--display);font-weight:700;font-size:1.3rem;color:var(--bright);margin-bottom:8px}
.mode-desc{color:var(--dim);line-height:1.6;font-size:12px}
.mode-badge{position:absolute;top:14px;right:14px;padding:3px 10px;border-radius:20px;
  font-size:10px;font-weight:700;letter-spacing:.05em}
.badge-auto{background:rgba(0,200,255,0.15);color:var(--cyan);border:1px solid rgba(0,200,255,0.3)}
.badge-guided{background:rgba(10,255,176,0.12);color:var(--green);border:1px solid rgba(10,255,176,0.25)}

/* STATS BAR */
.stats-bar{display:flex;gap:2px;max-width:900px;margin:0 auto 60px;padding:0 24px}
.stat-item{flex:1;background:var(--s1);border:1px solid var(--border);padding:16px 12px;text-align:center}
.stat-val{font-family:var(--display);font-size:1.8rem;font-weight:700;color:var(--cyan)}
.stat-lbl{font-size:10px;color:var(--dim);letter-spacing:.08em;margin-top:4px}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(6,10,15,0.85);backdrop-filter:blur(8px);
  z-index:200;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--s2);border:1px solid var(--border2);border-radius:12px;
  width:min(90vw,600px);padding:32px;position:relative}
.modal h2{font-family:var(--display);font-size:1.4rem;color:var(--bright);margin-bottom:24px}
.modal-close{position:absolute;top:16px;right:16px;background:none;border:none;
  color:var(--dim);cursor:pointer;font-size:18px;transition:color .2s}
.modal-close:hover{color:var(--red)}

/* FORM */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:11px;letter-spacing:.1em;color:var(--cyan);
  text-transform:uppercase;margin-bottom:8px}
.form-input,.form-select,.form-textarea{width:100%;background:var(--s3);border:1px solid var(--border2);
  color:var(--bright);padding:12px 14px;font-family:var(--mono);font-size:13px;border-radius:6px;
  outline:none;transition:border-color .2s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--cyan)}
.form-textarea{height:100px;resize:vertical}
.form-select option{background:var(--s3)}

.btn{padding:12px 28px;border:none;border-radius:6px;font-family:var(--mono);font-size:13px;
  font-weight:700;cursor:pointer;transition:all .2s;letter-spacing:.05em}
.btn-primary{background:var(--cyan);color:#000}
.btn-primary:hover{background:#30d8ff;transform:translateY(-1px)}
.btn-green{background:var(--green);color:#000}
.btn-green:hover{background:#2affbc}
.btn-outline{background:transparent;border:1px solid var(--border2);color:var(--text)}
.btn-outline:hover{border-color:var(--cyan);color:var(--cyan)}
.btn-full{width:100%;margin-top:8px}

/* RECENT DISCOVERIES */
.section{max-width:1100px;margin:0 auto 60px;padding:0 24px;position:relative;z-index:1}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.section-title{font-family:var(--display);font-size:1.2rem;color:var(--bright)}
.section-link{color:var(--cyan);text-decoration:none;font-size:11px;letter-spacing:.1em}

.discovery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.disc-card{background:var(--s1);border:1px solid var(--border);border-radius:10px;padding:20px;
  cursor:pointer;transition:all .2s}
.disc-card:hover{border-color:var(--cyan);transform:translateY(-2px)}
.disc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
.disc-target{font-family:var(--display);font-size:.95rem;color:var(--bright);flex:1;line-height:1.3}
.disc-score{font-size:11px;padding:3px 8px;border-radius:4px;font-weight:700;white-space:nowrap;margin-left:10px}
.score-high{background:rgba(10,255,176,0.12);color:var(--green)}
.score-med{background:rgba(255,215,0,0.12);color:var(--gold)}
.score-low{background:rgba(255,61,107,0.12);color:var(--red)}
.disc-hypo{color:var(--dim);font-size:11px;line-height:1.5;margin-bottom:12px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.disc-meta{display:flex;gap:8px;flex-wrap:wrap}
.disc-tag{font-size:10px;padding:2px 8px;border-radius:12px;background:var(--s3);
  color:var(--dim);border:1px solid var(--border)}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 20px;color:var(--dim)}
.empty-state .big{font-size:3rem;margin-bottom:16px}
</style>
</head>
<body>

<nav>
  <div class="logo">🔬 <span>Discovery</span> Engine</div>
  <div class="nav-links">
    <a href="index.php" class="active">Accueil</a>
    <a href="research.php">Recherche</a>
    <a href="discoveries.php">Découvertes</a>
    <a href="dashboard.php">Dashboard</a>
  </div>
</nav>

<div id="hero">
  <div class="hero-label">Science Hub — Discovery Engine v2.0</div>
  <h1 class="hero-title">Moteur de <span>Découverte</span><br>Scientifique Autonome</h1>
  <p class="hero-sub">Consultation de 14 APIs scientifiques avec vrais abstracts · Boucle de réévaluation itérative · Hypothèses validées par critique IA · Vraies données, vraies découvertes</p>
</div>

<div class="modes">
  <div class="mode-card" onclick="openModal('auto')">
    <span class="mode-badge badge-auto">AUTONOME</span>
    <div class="mode-icon">🤖</div>
    <div class="mode-title">Mode Autonome</div>
    <div class="mode-desc">L'IA choisit seule des cibles sous-étudiées, consulte toutes les sources, génère et valide des hypothèses en boucle jusqu'à une vraie découverte. Zéro interaction requise.</div>
  </div>
  <div class="mode-card" onclick="openModal('guided')">
    <span class="mode-badge badge-guided">GUIDÉ</span>
    <div class="mode-icon">💬</div>
    <div class="mode-title">Mode Guidé</div>
    <div class="mode-desc">Posez une question scientifique ou définissez un thème. L'IA décompose votre question, cherche des preuves dans les bases de données et invente une réponse validée par la recherche.</div>
  </div>
</div>

<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-val" id="stat-total">–</div>
    <div class="stat-lbl">Découvertes</div>
  </div>
  <div class="stat-item">
    <div class="stat-val" id="stat-high">–</div>
    <div class="stat-lbl">Score ≥ 70%</div>
  </div>
  <div class="stat-item">
    <div class="stat-val" id="stat-novelty">–%</div>
    <div class="stat-lbl">Nouveauté moy.</div>
  </div>
  <div class="stat-item">
    <div class="stat-val" id="stat-apis">14</div>
    <div class="stat-lbl">APIs actives</div>
  </div>
</div>

<div class="section">
  <div class="section-header">
    <div class="section-title">Découvertes Récentes</div>
    <a href="discoveries.php" class="section-link">Voir tout →</a>
  </div>
  <div class="discovery-grid" id="disc-grid">
    <div class="empty-state" style="grid-column:1/-1">
      <div class="big">🔭</div>
      <div>Aucune découverte encore. Lancez une session pour commencer !</div>
    </div>
  </div>
</div>

<!-- MODAL AUTO -->
<div class="modal-overlay" id="modal-auto">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('auto')">✕</button>
    <h2>🤖 Mode Autonome</h2>
    <div class="form-group">
      <label class="form-label">Domaine préféré</label>
      <select class="form-select" id="auto-domain">
        <option value="general">Général (tous domaines)</option>
        <option value="genetics">Génétique & Génomique</option>
        <option value="oncology">Oncologie</option>
        <option value="neurology">Neurologie</option>
        <option value="immunology">Immunologie</option>
        <option value="cardiology">Cardiologie</option>
        <option value="biochem">Biochimie</option>
        <option value="pharmacology">Pharmacologie</option>
        <option value="biophysics">Biophysique</option>
      </select>
    </div>
    <p style="color:var(--dim);font-size:12px;margin-bottom:20px;line-height:1.6">
      L'IA va sélectionner une cible sous-étudiée, consulter 14 APIs avec vrais abstracts, 
      générer une hypothèse, la critiquer et l'affiner en boucle jusqu'à une vraie découverte.
    </p>
    <button class="btn btn-primary btn-full" onclick="startAuto()">🚀 Lancer la recherche autonome</button>
  </div>
</div>

<!-- MODAL GUIDED -->
<div class="modal-overlay" id="modal-guided">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('guided')">✕</button>
    <h2>💬 Mode Guidé</h2>
    <div class="form-group">
      <label class="form-label">Votre question scientifique</label>
      <textarea class="form-textarea" id="guided-question" 
        placeholder="Ex: Quel est le rôle des mitochondries dans la maladie d'Alzheimer ? Ou : Y a-t-il un lien entre le microbiome intestinal et la dépression ?"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Domaine</label>
      <select class="form-select" id="guided-domain">
        <option value="general">Détection automatique</option>
        <option value="genetics">Génétique</option>
        <option value="neurology">Neurologie</option>
        <option value="oncology">Oncologie</option>
        <option value="immunology">Immunologie</option>
        <option value="cardiology">Cardiologie</option>
        <option value="biochem">Biochimie</option>
        <option value="pharmacology">Pharmacologie</option>
      </select>
    </div>
    <button class="btn btn-green btn-full" onclick="startGuided()">🔬 Lancer la recherche guidée</button>
  </div>
</div>

<script>
// Modal
function openModal(mode) { document.getElementById('modal-'+mode).classList.add('show') }
function closeModal(mode) { document.getElementById('modal-'+mode).classList.remove('show') }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('show') }))

// Start sessions
async function startAuto() {
  const domain = document.getElementById('auto-domain').value;
  closeModal('auto');
  try {
    const r = await fetch('engine.php', {
      method:'POST', 
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({action:'start_auto', domain})
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const d = await r.json();
    if (d.ok && d.session_id) {
      window.location.href = `research.php?session=${d.session_id}`;
    } else {
      throw new Error(d.error || 'Réponse invalide');
    }
  } catch(e) {
    console.error('Erreur startAuto:', e);
    alert('Erreur: ' + e.message);
  }
}

async function startGuided() {
  const question = document.getElementById('guided-question').value.trim();
  const domain   = document.getElementById('guided-domain').value;
  if (!question) { alert('Entrez une question'); return; }
  closeModal('guided');
  try {
    const r = await fetch('engine.php', {
      method:'POST', 
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({action:'start_guided', question, domain})
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const d = await r.json();
    if (d.ok && d.session_id) {
      window.location.href = `research.php?session=${d.session_id}`;
    } else {
      throw new Error(d.error || 'Réponse invalide');
    }
  } catch(e) {
    console.error('Erreur startGuided:', e);
    alert('Erreur: ' + e.message);
  }
}

// Load stats & recent discoveries
async function loadHome() {
  try {
    const [stats, discs] = await Promise.all([
      fetch('engine.php?action=stats').then(r=>r.json()),
      fetch('engine.php?action=list_discoveries&limit=6').then(r=>r.json())
    ]);
    if (stats.ok) {
      document.getElementById('stat-total').textContent = stats.total;
      document.getElementById('stat-high').textContent = stats.high_novelty;
      document.getElementById('stat-novelty').textContent = (stats.avg_novelty*100).toFixed(0)+'%';
    }
    if (discs.ok && discs.data.length > 0) {
      document.getElementById('disc-grid').innerHTML = discs.data.map(d => {
        const score = (d.novelty_score*50 + d.validation_score*50).toFixed(0);
        const cls   = score>=65?'score-high':score>=40?'score-med':'score-low';
        const kw    = JSON.parse(d.keywords||'[]').slice(0,3);
        return `<div class="disc-card" onclick="location.href='discovery.php?id=${d.id}'">
          <div class="disc-header">
            <div class="disc-target">${esc(d.target)}</div>
            <div class="disc-score ${cls}">${score}%</div>
          </div>
          <div class="disc-hypo">${esc(d.vulgarized||d.hypothesis||'')}</div>
          <div class="disc-meta">
            <span class="disc-tag">${d.domain}</span>
            <span class="disc-tag">${d.mode}</span>
            ${kw.map(k=>`<span class="disc-tag">${esc(k)}</span>`).join('')}
          </div>
        </div>`;
      }).join('');
    }
  } catch(e) { console.error(e) }
}

function esc(t) { const d=document.createElement('div');d.textContent=t||'';return d.innerHTML }
loadHome();
</script>
</body>
</html>
