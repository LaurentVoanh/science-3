<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recherche en cours — Discovery Engine</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#060a0f;--s1:#0b1118;--s2:#10181f;--s3:#161f28;
  --border:rgba(0,200,255,0.1);--border2:rgba(0,200,255,0.22);
  --cyan:#00c8ff;--green:#0affb0;--red:#ff3d6b;--gold:#ffd700;--orange:#ff9500;
  --text:#b8d4e8;--dim:#4a6a85;--bright:#e2f0ff;
  --mono:'Space Mono',monospace;--display:'Syne',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--mono);font-size:12px;overflow:hidden}

/* LAYOUT */
#app{display:grid;grid-template-rows:56px 1fr;height:100vh}
header{display:flex;align-items:center;justify-content:space-between;
  padding:0 20px;border-bottom:1px solid var(--border2);background:var(--s1);
  position:relative;z-index:10}
.h-brand{font-family:var(--display);font-weight:800;font-size:16px;color:var(--bright)}
.h-brand span{color:var(--cyan)}
.h-controls{display:flex;gap:10px;align-items:center}
#main{display:grid;grid-template-columns:1fr 340px;overflow:hidden}

/* LIVE PANEL */
#live-panel{display:grid;grid-template-rows:auto 1fr auto;padding:20px;gap:16px;overflow:hidden}
.phase-tracker{display:flex;gap:6px;flex-wrap:wrap}
.phase-step{padding:5px 12px;border-radius:20px;font-size:10px;font-weight:700;
  border:1px solid var(--border);color:var(--dim);letter-spacing:.05em;transition:all .3s}
.phase-step.active{background:rgba(0,200,255,0.12);border-color:var(--cyan);color:var(--cyan)}
.phase-step.done{background:rgba(10,255,176,0.1);border-color:rgba(10,255,176,0.3);color:var(--green)}

/* LOG FEED */
#log-feed{overflow-y:auto;border:1px solid var(--border);background:var(--s1);
  border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:4px}
.log-entry{padding:7px 10px;border-left:3px solid var(--border2);
  background:rgba(0,0,0,0.2);border-radius:0 4px 4px 0;animation:fadeIn .3s ease}
.log-entry.success{border-left-color:var(--green)}
.log-entry.warning{border-left-color:var(--gold)}
.log-entry.error{border-left-color:var(--red)}
.log-entry.info{border-left-color:var(--cyan)}
.log-msg{color:var(--text)}
.log-det{color:var(--dim);font-size:11px;margin-top:2px}
.log-time{color:var(--dim);font-size:10px;margin-top:2px}
@keyframes fadeIn{from{opacity:0;transform:translateX(-6px)}to{opacity:1;transform:none}}

/* STATUS CARD */
.status-card{background:var(--s2);border:1px solid var(--border2);border-radius:8px;padding:16px}
.status-label{font-size:10px;letter-spacing:.15em;color:var(--cyan);text-transform:uppercase;margin-bottom:10px}
.status-target{font-family:var(--display);font-size:1.1rem;color:var(--bright);margin-bottom:8px}
.progress-bar-outer{height:4px;background:var(--border);border-radius:2px;overflow:hidden;margin:10px 0}
.progress-bar-inner{height:100%;background:linear-gradient(90deg,var(--cyan),var(--green));
  transition:width .4s ease;border-radius:2px}
.status-phase{color:var(--dim);font-size:11px}

/* RESULT PANEL */
#result-panel{border-left:1px solid var(--border2);overflow-y:auto;background:var(--s1)}
.result-inner{padding:20px;display:none;flex-direction:column;gap:16px}
.result-inner.show{display:flex}
.result-section{background:var(--s2);border:1px solid var(--border);border-radius:8px;padding:16px}
.rs-label{font-size:10px;letter-spacing:.15em;color:var(--cyan);text-transform:uppercase;margin-bottom:8px}
.rs-value{color:var(--bright);line-height:1.7;font-size:12px}
.scores-row{display:flex;gap:12px}
.score-box{flex:1;text-align:center;background:var(--s3);border-radius:6px;padding:10px}
.score-num{font-family:var(--display);font-size:1.5rem;font-weight:700}
.score-lbl{font-size:10px;color:var(--dim);margin-top:2px}
.verdict-badge{display:inline-block;padding:6px 16px;border-radius:20px;font-weight:700;
  font-size:12px;margin-top:12px}
.verdict-discovery{background:rgba(10,255,176,0.15);color:var(--green);border:1px solid rgba(10,255,176,0.35)}
.verdict-partial{background:rgba(255,215,0,0.12);color:var(--gold);border:1px solid rgba(255,215,0,0.3)}
.verdict-pending{background:rgba(0,200,255,0.1);color:var(--cyan);border:1px solid rgba(0,200,255,0.25)}
.keywords-row{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.kw{padding:3px 10px;background:var(--s3);border:1px solid var(--border);
  border-radius:12px;font-size:10px;color:var(--dim)}
.btn{padding:10px 20px;border:none;border-radius:6px;font-family:var(--mono);
  font-size:12px;font-weight:700;cursor:pointer;transition:all .2s}
.btn-cyan{background:var(--cyan);color:#000}
.btn-cyan:hover{background:#30d8ff}
.btn-red{background:transparent;border:1px solid var(--border2);color:var(--dim)}
.btn-red:hover{border-color:var(--red);color:var(--red)}
.btn-green{background:var(--green);color:#000}
.btn-sm{padding:6px 14px;font-size:11px}

/* IDLE screen */
#idle-screen{display:flex;flex-direction:column;align-items:center;justify-content:center;
  height:100%;text-align:center;padding:40px;color:var(--dim)}
.idle-big{font-size:4rem;margin-bottom:20px}
.idle-title{font-family:var(--display);font-size:1.2rem;color:var(--bright);margin-bottom:12px}

/* LOADER */
.pulse{display:inline-block;width:8px;height:8px;border-radius:50%;
  background:var(--cyan);animation:pulse 1.2s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:.3;transform:scale(.8)}50%{opacity:1;transform:scale(1.2)}}

/* Controls */
.control-row{display:flex;gap:10px}
</style>
</head>
<body>
<div id="app">
  <header>
    <div class="h-brand">🔬 <span>Discovery</span> Engine — Recherche Live</div>
    <div class="h-controls">
      <span id="h-mode-badge" style="color:var(--dim);font-size:11px"></span>
      <div class="control-row">
        <button class="btn btn-cyan btn-sm" id="btn-run" onclick="toggleRun()">▶ Démarrer</button>
        <button class="btn btn-red btn-sm" onclick="resetSession()">↺ Reset</button>
        <a href="index.php" class="btn btn-red btn-sm" style="text-decoration:none">← Accueil</a>
      </div>
    </div>
  </header>

  <div id="main">
    <div id="live-panel">
      <div class="phase-tracker" id="phase-tracker">
        <div class="phase-step" data-phase="select_target">1. Cible</div>
        <div class="phase-step" data-phase="collect">2. Collecte</div>
        <div class="phase-step" data-phase="deep_collect">3. Approfondissement</div>
        <div class="phase-step" data-phase="synthesize">4. Synthèse IA</div>
        <div class="phase-step" data-phase="critique">5. Critique</div>
        <div class="phase-step" data-phase="reevaluate">6. Réévaluation</div>
        <div class="phase-step" data-phase="discovered">7. Découverte</div>
      </div>

      <div id="log-feed">
        <div class="log-entry info">
          <div class="log-msg">🔬 Système initialisé. Cliquez sur Démarrer.</div>
        </div>
      </div>

      <div class="status-card" id="status-card">
        <div class="status-label">Statut actuel</div>
        <div class="status-target" id="status-target">En attente...</div>
        <div class="progress-bar-outer"><div class="progress-bar-inner" id="progress-bar" style="width:0%"></div></div>
        <div class="status-phase" id="status-phase">Prêt au démarrage</div>
      </div>
    </div>

    <div id="result-panel">
      <div id="idle-screen">
        <div class="idle-big">🔭</div>
        <div class="idle-title">Résultats apparaîtront ici</div>
        <div>Cliquez Démarrer pour lancer<br>la boucle de découverte</div>
      </div>
      <div class="result-inner" id="result-inner">
        <div class="result-section" id="res-target-section">
          <div class="rs-label">Cible étudiée</div>
          <div class="rs-value" id="res-target">–</div>
        </div>
        <div class="result-section">
          <div class="rs-label">Scores</div>
          <div class="scores-row">
            <div class="score-box">
              <div class="score-num" id="res-novelty" style="color:var(--cyan)">–</div>
              <div class="score-lbl">Nouveauté</div>
            </div>
            <div class="score-box">
              <div class="score-num" id="res-confidence" style="color:var(--green)">–</div>
              <div class="score-lbl">Confiance</div>
            </div>
            <div class="score-box">
              <div class="score-num" id="res-validity" style="color:var(--gold)">–</div>
              <div class="score-lbl">Validité</div>
            </div>
          </div>
          <div id="res-verdict-container"></div>
        </div>
        <div class="result-section">
          <div class="rs-label">Hypothèse</div>
          <div class="rs-value" id="res-hypo">–</div>
        </div>
        <div class="result-section" id="res-vuln-section">
          <div class="rs-label">En termes simples</div>
          <div class="rs-value" id="res-vulg" style="color:var(--dim)">–</div>
        </div>
        <div class="result-section">
          <div class="rs-label">Mécanisme</div>
          <div class="rs-value" id="res-mech" style="color:var(--dim)">–</div>
        </div>
        <div class="result-section" id="res-kw-section">
          <div class="rs-label">Mots-clés</div>
          <div class="keywords-row" id="res-keywords"></div>
        </div>
        <div style="display:flex;gap:8px;padding:0 0 20px">
          <button class="btn btn-green btn-sm" id="btn-save-discovery" onclick="viewDiscovery()" style="display:none">
            📄 Voir la découverte complète
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const params    = new URLSearchParams(location.search);
const sessionId = params.get('session');
if (!sessionId) location.href = 'index.php';

let running  = false;
let loopTimer= null;
let lastPhase= '';
let discoveryId = null;
const STEP_INTERVAL = 1200; // ms entre étapes (PHP a besoin de temps)

// Phase labels
const PHASE_LABELS = {
  select_target: 'Sélection de la cible...',
  collect:       'Collecte des sources...',
  deep_collect:  'Approfondissement des requêtes...',
  synthesize:    'Synthèse IA — génération d\'hypothèse...',
  critique:      'Critique scientifique...',
  reevaluate:    'Réévaluation et validation...',
  discovered:    '🏆 Découverte validée !',
};

const PHASE_PROGRESS = {
  select_target: 10, collect: 30, deep_collect: 45,
  synthesize: 65, critique: 80, reevaluate: 90, discovered: 100
};

// Init
async function init() {
  const r = await apiFetch('get_state');
  if (r.ok && r.state) {
    updateUI(r.state, r.logs||[]);
    const mode = r.state.mode === 'guided' ? '💬 GUIDÉ' : '🤖 AUTONOME';
    document.getElementById('h-mode-badge').textContent = mode;
  }
}

async function toggleRun() {
  if (running) { stopLoop(); } 
  else { startLoop(); }
}

function startLoop() {
  running = true;
  document.getElementById('btn-run').textContent = '⏸ Pause';
  document.getElementById('btn-run').style.background = 'var(--orange)';
  runStep();
}

function stopLoop() {
  running = false;
  clearTimeout(loopTimer);
  document.getElementById('btn-run').textContent = '▶ Reprendre';
  document.getElementById('btn-run').style.background = 'var(--cyan)';
}

async function runStep() {
  if (!running) return;
  try {
    const r = await apiFetch('step', {session_id: sessionId});
    if (r.ok) {
      updateUI(r.state, r.logs||[]);
      if (r.state?.phase === 'discovered') {
        stopLoop();
        return;
      }
      loopTimer = setTimeout(runStep, STEP_INTERVAL);
    } else {
      addLogEntry({log_type:'error', message:'Erreur: ' + r.error, details:''});
      stopLoop();
    }
  } catch(e) {
    addLogEntry({log_type:'error', message:'Erreur réseau', details: e.message});
    stopLoop();
  }
}

async function resetSession() {
  stopLoop();
  if (!confirm('Réinitialiser cette session ?')) return;
  await apiFetch('reset', {session_id: sessionId});
  location.href = 'index.php';
}

function updateUI(state, logs) {
  if (!state) return;
  const phase = state.phase || 'select_target';

  // Phase tracker
  const phases = ['select_target','collect','deep_collect','synthesize','critique','reevaluate','discovered'];
  const ci = phases.indexOf(phase);
  document.querySelectorAll('.phase-step').forEach((el, i) => {
    el.classList.remove('active','done');
    if (i < ci) el.classList.add('done');
    else if (i === ci) el.classList.add('active');
  });

  // Status
  document.getElementById('status-target').textContent = state.target || 'Initialisation...';
  document.getElementById('status-phase').textContent = PHASE_LABELS[phase] || phase;
  document.getElementById('progress-bar').style.width = (PHASE_PROGRESS[phase]||0) + '%';

  // Result panel
  if (state.hypothesis) {
    document.getElementById('idle-screen').style.display = 'none';
    document.getElementById('result-inner').classList.add('show');

    document.getElementById('res-target').textContent    = state.target || '–';
    document.getElementById('res-hypo').textContent      = state.hypothesis || '–';
    document.getElementById('res-vulg').textContent      = state.vulgarized || '–';
    document.getElementById('res-mech').textContent      = state.mechanism || '–';
    document.getElementById('res-novelty').textContent   = Math.round((state.novelty_score||0)*100) + '%';
    document.getElementById('res-confidence').textContent= Math.round((state.confidence||0)*100) + '%';
    document.getElementById('res-validity').textContent  = Math.round((state.validation_score||0)*100) + '%';

    // Keywords
    const kws = Array.isArray(state.keywords) ? state.keywords : JSON.parse(state.keywords||'[]');
    document.getElementById('res-keywords').innerHTML = kws.map(k=>`<span class="kw">${esc(k)}</span>`).join('');

    // Verdict
    const verdict = state.final_verdict || 'pending';
    const vmap = {discovery: ['verdict-discovery','🏆 DÉCOUVERTE VALIDÉE'], partial: ['verdict-partial','📝 Résultat Partiel'], pending: ['verdict-pending','⏳ En cours...']};
    const [vcls, vlabel] = vmap[verdict] || vmap.pending;
    document.getElementById('res-verdict-container').innerHTML = `<span class="verdict-badge ${vcls}">${vlabel}</span>`;

    if (verdict === 'discovery' || verdict === 'partial') {
      document.getElementById('btn-save-discovery').style.display = 'inline-block';
    }
  }

  // Logs
  if (logs && logs.length > 0) {
    const feed = document.getElementById('log-feed');
    const existingCount = feed.querySelectorAll('.log-entry').length;
    if (logs.length > existingCount || lastPhase !== phase) {
      feed.innerHTML = '';
      logs.slice(-60).forEach(log => {
        const el = document.createElement('div');
        el.className = `log-entry ${log.log_type||'info'}`;
        el.innerHTML = `<div class="log-msg">${esc(log.message||'')}</div>
          ${log.details ? `<div class="log-det">${esc(log.details)}</div>` : ''}
          <div class="log-time">${(log.created_at||'').split(' ')[1]||''}</div>`;
        feed.appendChild(el);
      });
      feed.scrollTop = feed.scrollHeight;
    }
  }
  lastPhase = phase;
}

function addLogEntry(log) {
  const feed = document.getElementById('log-feed');
  const el = document.createElement('div');
  el.className = `log-entry ${log.log_type||'info'}`;
  el.innerHTML = `<div class="log-msg">${esc(log.message||'')}</div>
    ${log.details?`<div class="log-det">${esc(log.details)}</div>`:''}`;
  feed.appendChild(el);
  feed.scrollTop = feed.scrollHeight;
}

function viewDiscovery() {
  location.href = 'discoveries.php';
}

async function apiFetch(action, data = {}) {
  const fd = new FormData();
  fd.append('action', action);
  fd.append('session_id', sessionId);
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  const r = await fetch('engine.php', {method:'POST', body: fd});
  return r.json();
}

function esc(t) { const d=document.createElement('div');d.textContent=String(t||'');return d.innerHTML }

init();
</script>
</body>
</html>
