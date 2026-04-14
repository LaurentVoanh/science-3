<?php
/**
 * DISCOVERY ENGINE — engine.php
 * Moteur de découverte : boucle itérative, réévaluation, vrais abstracts
 */

// Inclure config.php EN PREMIER pour initialiser les sessions avant tout header
if (!defined('DE_VERSION')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/apis.php';

// Récupérer session_id AVANT les headers
$session_id = $_POST['session_id'] ?? ($_SESSION['discovery_session'] ?? null);

// Si pas de session_id, en créer un nouveau
if (!$session_id) {
    $session_id = bin2hex(random_bytes(16));
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['discovery_session'] = $session_id;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? '';

ob_start();

try {
    switch ($action) {

        // ── DÉMARRER UNE SESSION AUTONOME ─────────────────────────────────
        case 'start_auto':
            $domain = $_POST['domain'] ?? 'general';
            $state  = init_state($session_id, 'auto', '', $domain);
            save_state($session_id, $state);
            send(['ok'=>true,'state'=>$state,'session_id'=>$session_id]);

        // ── DÉMARRER AVEC UNE QUESTION ────────────────────────────────────
        case 'start_guided':
            $question = trim($_POST['question'] ?? '');
            $domain   = $_POST['domain'] ?? 'general';
            if (!$question) throw new Exception('Question manquante');
            $state = init_state($session_id, 'guided', $question, $domain);
            save_state($session_id, $state);
            send(['ok'=>true,'state'=>$state,'session_id'=>$session_id]);

        // ── EXÉCUTER L'ÉTAPE SUIVANTE ─────────────────────────────────────
        case 'step':
            $state = load_state($session_id);
            if (!$state) throw new Exception('Session introuvable');
            $state = execute_step($state);
            save_state($session_id, $state);
            send(['ok'=>true,'state'=>$state,'phase'=>$state['phase'],'step'=>$state['step'],'logs'=>get_logs($session_id, 30)]);

        // ── RÉCUPÉRER L'ÉTAT ──────────────────────────────────────────────
        case 'get_state':
            $state = load_state($session_id);
            $logs  = get_logs($session_id, 50);
            send(['ok'=>true,'state'=>$state,'logs'=>$logs]);

        // ── RESET ─────────────────────────────────────────────────────────
        case 'reset':
            delete_state($session_id);
            send(['ok'=>true]);

        // ── LISTE DES DÉCOUVERTES ─────────────────────────────────────────
        case 'list_discoveries':
            $pdo   = get_db();
            $limit = (int)($_GET['limit'] ?? 50);
            $stmt  = $pdo->prepare("SELECT * FROM discoveries ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            send(['ok'=>true,'data'=>$stmt->fetchAll()]);

        // ── DÉTAIL D'UNE DÉCOUVERTE ───────────────────────────────────────
        case 'get_discovery':
            $id  = (int)($_GET['id'] ?? 0);
            $pdo = get_db();
            $d   = $pdo->prepare("SELECT * FROM discoveries WHERE id=?");
            $d->execute([$id]);
            $row = $d->fetch();
            if (!$row) throw new Exception('Not found');
            $s = $pdo->prepare("SELECT * FROM source_results WHERE discovery_id=?");
            $s->execute([$id]);
            send(['ok'=>true,'data'=>$row,'sources'=>$s->fetchAll()]);

        // ── STATS ─────────────────────────────────────────────────────────
        case 'stats':
            $pdo = get_db();
            $total   = $pdo->query("SELECT COUNT(*) FROM discoveries")->fetchColumn();
            $high    = $pdo->query("SELECT COUNT(*) FROM discoveries WHERE novelty_score >= 0.7")->fetchColumn();
            $avg_nov = $pdo->query("SELECT AVG(novelty_score) FROM discoveries")->fetchColumn();
            $avg_conf= $pdo->query("SELECT AVG(confidence) FROM discoveries")->fetchColumn();
            send(['ok'=>true,'total'=>(int)$total,'high_novelty'=>(int)$high,'avg_novelty'=>round((float)$avg_nov,2),'avg_confidence'=>round((float)$avg_conf,2)]);

        default:
            throw new Exception("Action inconnue: {$action}");
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// MOTEUR DE DÉCOUVERTE — ÉTAPES
// ═══════════════════════════════════════════════════════════════════════════════

function execute_step(array $state): array {
    global $MISTRAL_CONFIG;
    $phase = $state['phase'] ?? 'select_target';
    $iter  = $state['iteration'] ?? 0;
    $step  = $state['step'] ?? 0;

    // ── PHASE 1 : SÉLECTION DE CIBLE ──────────────────────────────────────
    if ($phase === 'select_target') {
        return step_select_target($state);
    }

    // ── PHASE 2 : COLLECTE LARGE (toutes sources pertinentes) ─────────────
    if ($phase === 'collect') {
        return step_collect($state);
    }

    // ── PHASE 3 : REQUÊTES COMPLÉMENTAIRES si résultats faibles ───────────
    if ($phase === 'deep_collect') {
        return step_deep_collect($state);
    }

    // ── PHASE 4 : SYNTHÈSE ET GÉNÉRATION D'HYPOTHÈSE ──────────────────────
    if ($phase === 'synthesize') {
        return step_synthesize($state);
    }

    // ── PHASE 5 : CRITIQUE / VALIDATION ───────────────────────────────────
    if ($phase === 'critique') {
        return step_critique($state);
    }

    // ── PHASE 6 : RÉÉVALUATION ────────────────────────────────────────────
    if ($phase === 'reevaluate') {
        return step_reevaluate($state);
    }

    // ── PHASE 7 : DÉCOUVERTE VALIDÉE ──────────────────────────────────────
    if ($phase === 'discovered') {
        return $state; // déjà terminé
    }

    return $state;
}

// ── ÉTAPE : SÉLECTION DE CIBLE ────────────────────────────────────────────────
function step_select_target(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];

    if ($state['mode'] === 'guided') {
        // Mode guidé : l'IA décompose la question en cible + angle
        add_log($session_id, 0, 'select_target', '🔍 Analyse de votre question...', $state['question'], 'info');

        $result = shu_mistral([
            ['role'=>'system', 'content'=>PROMPT_GUIDED_DECOMPOSITION],
            ['role'=>'user', 'content'=>"Question: " . $state['question']]
        ], $MISTRAL_CONFIG['default_model'], 1500, 0.4);

        if ($result['success'] && !empty($result['data']['research_target'])) {
            $d = $result['data'];
            $state['target']        = $d['research_target'];
            $state['domain']        = $d['domain'] ?? $state['domain'];
            $state['target_angle']  = $d['research_angle'] ?? '';
            $state['target_queries']= $d['search_queries'] ?? [$d['research_target']];
            $state['sources']       = get_sources_for_domain($state['domain']);
            add_log($session_id, 0, 'select_target', "🎯 Cible: {$state['target']}", "Angle: {$state['target_angle']}", 'success');
        } else {
            // Fallback
            $state['target']        = $state['question'];
            $state['target_queries']= [preg_replace('/[^A-Za-z0-9 ]/', '', $state['question'])];
            $state['sources']       = get_sources_for_domain($state['domain']);
            add_log($session_id, 0, 'select_target', '⚠️ Décomposition directe (fallback)', '', 'warning');
        }
    } else {
        // Mode auto : l'IA choisit une cible sous-étudiée
        $already = array_slice($state['explored_targets'] ?? [], -8);
        add_log($session_id, 0, 'select_target', '🤖 Sélection de cible autonome...', 'Exploration en cours', 'info');

        $result = shu_mistral([
            ['role'=>'system', 'content'=>PROMPT_TARGET_SELECTION],
            ['role'=>'user', 'content'=>"Domaine prioritaire: {$state['domain']}. Cibles déjà explorées (ÉVITE): [" . implode(', ', $already) . "]"]
        ], $MISTRAL_CONFIG['default_model'], 1500, 0.85);

        if ($result['success'] && !empty($result['data']['next_target'])) {
            $d = $result['data'];
            $target = trim($d['next_target']);
            $invalid = ['array','object','null','json','test','target'];
            if (strlen($target) < 3 || in_array(strtolower($target), $invalid) || in_array($target, $already)) {
                $fb = ['Maladie de Niemann-Pick','Syndrome de Cockayne','Déficit en GAMT','Maladie de Krabbe','Ataxie de Friedreich'];
                $target = $fb[array_rand($fb)];
            }
            $state['target']        = $target;
            $state['domain']        = $d['domain'] ?? $state['domain'];
            $state['target_angle']  = $d['research_angle'] ?? '';
            $state['target_queries']= $d['suggested_queries'] ?? [$target];
            $state['sources']       = get_sources_for_domain($state['domain']);
            $state['explored_targets'][] = $target;
            add_log($session_id, 0, 'select_target', "🎯 Cible: {$target}", "Domaine: {$state['domain']} | Angle: {$state['target_angle']}", 'success');
        } else {
            $fb = ['Syndrome FIRES','Maladie de Unverricht-Lundborg','Syndrome de Mohr-Tranebjærg'];
            $state['target'] = $fb[array_rand($fb)];
            $state['target_queries'] = [$state['target']];
            $state['sources'] = get_sources_for_domain('general');
            add_log($session_id, 0, 'select_target', '⚠️ Cible fallback', $result['error']??'', 'warning');
        }
    }

    $state['memory']       = [];
    $state['total_hits']   = 0;
    $state['sources_done'] = [];
    $state['source_index'] = 0;
    $state['phase']        = 'collect';
    $state['step']         = 1;
    return $state;
}

// ── ÉTAPE : COLLECTE SUR TOUTES LES SOURCES ───────────────────────────────────
function step_collect(array $state): array {
    $session_id   = $state['session_id'];
    $sources      = $state['sources'] ?? get_sources_for_domain('general');
    $source_index = $state['source_index'] ?? 0;

    if ($source_index >= count($sources)) {
        // Toutes les sources consultées — vérifier si on a assez de données
        $total_hits  = array_sum(array_column($state['memory']??[], 'hits'));
        $state['total_hits'] = $total_hits;

        if ($total_hits < 15) {
            // Résultats insuffisants → recherche approfondie avec termes alternatifs
            add_log($session_id, $state['step'], 'collect', "⚠️ Seulement {$total_hits} résultats — approfondissement...", '', 'warning');
            $state['phase']         = 'deep_collect';
            $state['deep_pass']     = 0;
        } else {
            add_log($session_id, $state['step'], 'collect', "✅ Collecte terminée: {$total_hits} résultats sur " . count($state['memory']) . " sources", '', 'success');
            $state['phase'] = 'synthesize';
        }
        return $state;
    }

    // Consulter la source courante
    $source = $sources[$source_index];
    $queries = $state['target_queries'] ?? [$state['target']];
    $query   = $queries[$source_index % count($queries)];
    $query   = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $query);

    add_log($session_id, $state['step'], 'collect', "📡 {$source} ← \"{$query}\"", '', 'info');

    $result = call_source($source, $query, 8);
    $hits   = $result['hits'] ?? 0;
    $type   = $hits > 0 ? 'success' : 'warning';
    $emoji  = $hits > 5 ? '✅' : ($hits > 0 ? '⚡' : '⚠️');

    $state['memory'][] = [
        'source'    => $source,
        'query'     => $query,
        'hits'      => $hits,
        'items'     => $result['items'] ?? [],
        'abstracts' => $result['abstracts'] ?? '',
    ];
    $state['sources_done'][] = $source;

    add_log($session_id, $state['step'], 'collect', "{$emoji} {$source}: {$hits} résultats", "Requête: \"{$query}\"", $type);

    $state['source_index'] = $source_index + 1;
    $state['step']++;
    return $state;
}

// ── ÉTAPE : APPROFONDISSEMENT ──────────────────────────────────────────────────
function step_deep_collect(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];
    $pass       = $state['deep_pass'] ?? 0;

    if ($pass >= 3) {
        // 3 passes d'approfondissement → on synthétise quand même
        add_log($session_id, $state['step'], 'deep_collect', '🔄 Approfondissement terminé → synthèse', '', 'info');
        $state['phase'] = 'synthesize';
        return $state;
    }

    // L'IA génère des requêtes alternatives
    $current_results = array_sum(array_column($state['memory']??[], 'hits'));
    add_log($session_id, $state['step'], 'deep_collect', "🧠 Génération requêtes alternatives (pass {$pass})...", '', 'info');

    $mem_summary = '';
    foreach (($state['memory']??[]) as $m) {
        $mem_summary .= "{$m['source']} ({$m['hits']} hits, query: \"{$m['query']}\")\n";
    }

    $result = shu_mistral([
        ['role'=>'system', 'content'=>PROMPT_QUERY_OPTIMIZER],
        ['role'=>'user', 'content'=>"Cible: {$state['target']}\nDomaine: {$state['domain']}\nRésultats actuels ({$current_results} hits total):\n{$mem_summary}\n\nGénère 4 requêtes alternatives plus spécifiques ou avec synonymes pour trouver plus de résultats."]
    ], $MISTRAL_CONFIG['default_model'], 800, 0.6);

    $new_queries = [];
    if ($result['success'] && !empty($result['data']['queries'])) {
        $new_queries = array_slice((array)$result['data']['queries'], 0, 4);
    } else {
        // Fallback : varier le terme
        $base = $state['target'];
        $new_queries = [
            $base . ' molecular mechanism',
            $base . ' pathogenesis',
            $base . ' treatment',
            str_replace(' ', '_', $base),
        ];
    }

    add_log($session_id, $state['step'], 'deep_collect', "🔎 " . count($new_queries) . " nouvelles requêtes générées", implode(', ', array_map(fn($q)=>"\"$q\"", $new_queries)), 'info');

    // Exécuter les nouvelles requêtes sur les sources les plus fertiles
    $fertile_sources = ['pubmed','europepmc','openalex','semantic_scholar'];
    foreach ($new_queries as $q) {
        foreach (array_slice($fertile_sources, 0, 2) as $src) {
            $q_clean = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $q);
            $r = call_source($src, $q_clean, 6);
            if (($r['hits']??0) > 0) {
                $state['memory'][] = ['source'=>$src,'query'=>$q_clean,'hits'=>$r['hits'],'items'=>$r['items']??[],'abstracts'=>$r['abstracts']??''];
                add_log($session_id, $state['step'], 'deep_collect', "✅ {$src}: {$r['hits']} hits sur \"{$q_clean}\"", '', 'success');
            }
        }
    }

    $state['deep_pass'] = $pass + 1;
    $total = array_sum(array_column($state['memory']??[], 'hits'));

    if ($total >= 15) {
        add_log($session_id, $state['step'], 'deep_collect', "✅ {$total} résultats → synthèse", '', 'success');
        $state['phase'] = 'synthesize';
    } else {
        $state['step']++;
    }
    return $state;
}

// ── ÉTAPE : SYNTHÈSE ──────────────────────────────────────────────────────────
function step_synthesize(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];

    $valid = array_filter($state['memory']??[], fn($m)=>($m['hits']??0)>0);
    if (count($valid) < 2) {
        add_log($session_id, $state['step'], 'synthesize', '❌ Sources insuffisantes, nouvelle cible', '', 'error');
        $state['phase']      = 'select_target';
        $state['iteration']  = ($state['iteration']??0) + 1;
        $state['memory']     = [];
        return $state;
    }

    // Construire le contexte enrichi
    $ctx  = "CIBLE DE RECHERCHE: {$state['target']}\n";
    $ctx .= "DOMAINE: {$state['domain']}\n";
    if (!empty($state['target_angle'])) $ctx .= "ANGLE DE RECHERCHE: {$state['target_angle']}\n";
    if (!empty($state['question']))     $ctx .= "QUESTION INITIALE: {$state['question']}\n";
    $ctx .= "\n--- DONNÉES COLLECTÉES (" . count($valid) . " sources, " . array_sum(array_column($valid,'hits')) . " résultats) ---\n\n";

    $total_abstracts = 0;
    foreach ($valid as $m) {
        if (empty($m['abstracts'])) continue;
        $ctx .= "=== {$m['source']} ({$m['hits']} résultats) — Requête: \"{$m['query']}\" ===\n";
        $ctx .= substr($m['abstracts'], 0, 1200) . "\n\n";
        $total_abstracts += strlen($m['abstracts']);
    }

    add_log($session_id, $state['step'], 'synthesize', '🧠 Synthèse IA — génération hypothèse...', count($valid) . ' sources, ' . round($total_abstracts/1000, 1) . 'KB de données', 'info');

    $result = shu_mistral([
        ['role'=>'system', 'content'=>PROMPT_HYPOTHESIS_GENERATION],
        ['role'=>'user', 'content'=>$ctx]
    ], $MISTRAL_CONFIG['deep_model'], 3500, 0.45);

    if ($result['success'] && !empty($result['data']['hypothesis'])) {
        $d = $result['data'];
        $state['hypothesis']       = $d['hypothesis'];
        $state['vulgarized']       = $d['vulgarized'] ?? '';
        $state['mechanism']        = $d['mechanism'] ?? '';
        $state['novelty_score']    = (float)($d['novelty_score'] ?? 0.5);
        $state['confidence']       = (float)($d['confidence'] ?? 0.5);
        $state['evidence_strength']= $d['evidence_strength'] ?? 'moderate';
        $state['keywords']         = $d['keywords'] ?? [];
        $state['actionable']       = $d['actionable'] ?? '';
        $state['total_abstracts']  = count($valid);

        add_log($session_id, $state['step'], 'synthesize', '💡 Hypothèse générée! Score nouveauté: ' . round($state['novelty_score']*100) . '%', substr($state['hypothesis'],0,120).'...', 'success');
        $state['phase'] = 'critique';
    } else {
        add_log($session_id, $state['step'], 'synthesize', '❌ Échec synthèse', $result['error']??'', 'error');
        $state['phase']     = 'select_target';
        $state['iteration'] = ($state['iteration']??0) + 1;
        $state['memory']    = [];
    }
    $state['step']++;
    return $state;
}

// ── ÉTAPE : CRITIQUE ──────────────────────────────────────────────────────────
function step_critique(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];

    add_log($session_id, $state['step'], 'critique', '⚖️ Critique scientifique rigoureuse...', '', 'info');

    $sources_summary = implode(', ', array_column(
        array_filter($state['memory']??[], fn($m)=>($m['hits']??0)>0), 'source'
    ));

    $result = shu_mistral([
        ['role'=>'system', 'content'=>PROMPT_CRITIQUE],
        ['role'=>'user', 'content'=>"HYPOTHÈSE À CRITIQUER:\n{$state['hypothesis']}\n\nMÉCANISME PROPOSÉ:\n{$state['mechanism']}\n\nSOURCES UTILISÉES: {$sources_summary}\n\nNOMBRE DE RÉSULTATS: " . array_sum(array_column($state['memory']??[], 'hits'))]
    ], $MISTRAL_CONFIG['deep_model'], 2500, 0.35);

    if ($result['success'] && isset($result['data']['validity_score'])) {
        $d = $result['data'];
        $state['validation_score']  = (float)($d['validity_score'] ?? 0.5);
        $state['critique']          = json_encode($d);
        $state['critique_verdict']  = $d['verdict'] ?? 'revise';

        $score_pct = round($state['validation_score'] * 100);
        add_log($session_id, $state['step'], 'critique', "⚖️ Critique: {$score_pct}% validité | Verdict: {$state['critique_verdict']}", '', $state['validation_score'] >= 0.6 ? 'success' : 'warning');
        $state['phase'] = 'reevaluate';
    } else {
        add_log($session_id, $state['step'], 'critique', '⚠️ Critique partielle', $result['error']??'', 'warning');
        $state['validation_score'] = 0.5;
        $state['critique_verdict'] = 'revise';
        $state['phase'] = 'reevaluate';
    }
    $state['step']++;
    return $state;
}

// ── ÉTAPE : RÉÉVALUATION ──────────────────────────────────────────────────────
function step_reevaluate(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];
    $iteration  = $state['iteration'] ?? 0;

    $validity   = $state['validation_score'] ?? 0.5;
    $novelty    = $state['novelty_score'] ?? 0.5;
    $verdict    = $state['critique_verdict'] ?? 'revise';
    $combined   = ($validity * 0.5) + ($novelty * 0.5);

    add_log($session_id, $state['step'], 'reevaluate', "🔄 Réévaluation iter {$iteration} — Score: " . round($combined*100) . "% | Verdict: {$verdict}", '', 'info');

    // Conditions de validation
    $is_discovery = ($combined >= 0.65 && in_array($verdict, ['accept','accept with caution'])) ||
                    ($combined >= 0.75) ||
                    ($iteration >= 3 && $combined >= 0.5);

    if ($is_discovery) {
        // DÉCOUVERTE VALIDÉE
        $state['final_verdict'] = 'discovery';
        $state['phase']         = 'discovered';
        save_discovery($state);
        add_log($session_id, $state['step'], 'reevaluate', '🏆 DÉCOUVERTE VALIDÉE! Score final: ' . round($combined*100) . '%', $state['hypothesis'], 'success');
    } elseif ($iteration >= 4) {
        // Trop d'itérations → sauvegarder comme partiel et continuer
        $state['final_verdict'] = 'partial';
        $state['phase']         = 'discovered';
        save_discovery($state);
        add_log($session_id, $state['step'], 'reevaluate', '📝 Résultat partiel sauvegardé après ' . $iteration . ' itérations', '', 'warning');
    } elseif ($verdict === 'reject' || $combined < 0.3) {
        // Rejeter et changer de cible
        add_log($session_id, $state['step'], 'reevaluate', '❌ Hypothèse rejetée → nouvelle cible', '', 'error');
        $state['phase']      = 'select_target';
        $state['iteration']  = $iteration + 1;
        $state['memory']     = [];
        $state['hypothesis'] = '';
    } else {
        // Améliorer : chercher des sources supplémentaires ciblées sur les lacunes
        add_log($session_id, $state['step'], 'reevaluate', '🔍 Amélioration en cours — recherche des lacunes...', '', 'info');
        $state = find_missing_evidence($state);
        $state['iteration'] = $iteration + 1;
        $state['phase']     = 'synthesize';
    }

    $state['step']++;
    return $state;
}

// ── RECHERCHE DES PREUVES MANQUANTES ──────────────────────────────────────────
function find_missing_evidence(array $state): array {
    global $MISTRAL_CONFIG;
    $session_id = $state['session_id'];

    $critique_data = @json_decode($state['critique']??'{}', true) ?? [];
    $missing       = $critique_data['missing_evidence'] ?? [];
    $rec_sources   = $critique_data['recommended_additional_sources'] ?? [];

    if (empty($missing) && empty($rec_sources)) {
        // Pas d'indication → tenter des requêtes plus spécifiques
        $missing = [$state['hypothesis'] . ' mechanism', $state['target'] . ' genetic basis'];
    }

    add_log($session_id, $state['step'], 'reevaluate', '🔎 Recherche preuves manquantes: ' . implode(', ', array_slice($missing,0,2)), '', 'info');

    foreach (array_slice($missing, 0, 3) as $gap) {
        $q = preg_replace('/[^A-Za-z0-9 \-_]/', '', $gap);
        if (!$q) continue;
        foreach (['pubmed','openalex','europepmc'] as $src) {
            $r = call_source($src, $q, 5);
            if (($r['hits']??0) > 0) {
                $state['memory'][] = ['source'=>$src,'query'=>$q,'hits'=>$r['hits'],'items'=>$r['items']??[],'abstracts'=>$r['abstracts']??''];
                add_log($session_id, $state['step'], 'reevaluate', "✅ Preuve complémentaire: {$src} ({$r['hits']} hits)", "Query: \"{$q}\"", 'success');
            }
        }
    }
    return $state;
}

// ── SAUVEGARDER LA DÉCOUVERTE ────────────────────────────────────────────────
function save_discovery(array $state): void {
    $pdo = get_db();
    if (!$pdo) return;

    $stmt = $pdo->prepare("INSERT INTO discoveries (session_id,mode,question,target,domain,hypothesis,vulgarized,mechanism,novelty_score,confidence,evidence_strength,sources_consulted,total_abstracts,validation_score,critique,final_verdict,iterations,keywords,actionable) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $state['session_id'],
        $state['mode'] ?? 'auto',
        $state['question'] ?? '',
        $state['target'] ?? '',
        $state['domain'] ?? 'general',
        $state['hypothesis'] ?? '',
        $state['vulgarized'] ?? '',
        $state['mechanism'] ?? '',
        $state['novelty_score'] ?? 0,
        $state['confidence'] ?? 0,
        $state['evidence_strength'] ?? 'moderate',
        json_encode(array_column(array_filter($state['memory']??[], fn($m)=>($m['hits']??0)>0), 'source')),
        $state['total_abstracts'] ?? 0,
        $state['validation_score'] ?? 0,
        $state['critique'] ?? '',
        $state['final_verdict'] ?? 'pending',
        $state['iteration'] ?? 0,
        json_encode($state['keywords'] ?? []),
        $state['actionable'] ?? '',
    ]);

    $disc_id = $pdo->lastInsertId();

    // Sauvegarder les sources
    $s2 = $pdo->prepare("INSERT INTO source_results (discovery_id,session_id,source,query,hits,abstracts_text) VALUES (?,?,?,?,?,?)");
    foreach ($state['memory']??[] as $m) {
        if (($m['hits']??0) > 0) {
            $s2->execute([$disc_id, $state['session_id'], $m['source'], $m['query'], $m['hits'], substr($m['abstracts']??'',0,2000)]);
        }
    }
}

// ── STATE MANAGEMENT ──────────────────────────────────────────────────────────
function init_state(string $session_id, string $mode, string $question, string $domain): array {
    return [
        'session_id'       => $session_id,
        'mode'             => $mode,
        'question'         => $question,
        'domain'           => $domain,
        'phase'            => 'select_target',
        'step'             => 0,
        'iteration'        => 0,
        'target'           => '',
        'target_angle'     => '',
        'target_queries'   => [],
        'sources'          => [],
        'source_index'     => 0,
        'memory'           => [],
        'explored_targets' => [],
        'hypothesis'       => '',
        'vulgarized'       => '',
        'mechanism'        => '',
        'novelty_score'    => 0,
        'confidence'       => 0,
        'validation_score' => 0,
        'critique'         => '',
        'critique_verdict' => '',
        'final_verdict'    => 'pending',
        'keywords'         => [],
        'actionable'       => '',
        'started_at'       => time(),
    ];
}

function save_state(string $session_id, array $state): void {
    $file = STORAGE_PATH . "state/state_{$session_id}.json";
    file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT));
}

function load_state(string $session_id): ?array {
    $file = STORAGE_PATH . "state/state_{$session_id}.json";
    return file_exists($file) ? @json_decode(file_get_contents($file), true) : null;
}

function delete_state(string $session_id): void {
    $file = STORAGE_PATH . "state/state_{$session_id}.json";
    if (file_exists($file)) unlink($file);
    $pdo = get_db();
    if ($pdo) $pdo->prepare("DELETE FROM research_logs WHERE session_id=?")->execute([$session_id]);
}

function send(array $d): void {
    while (ob_get_level()) ob_end_clean();
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROMPTS IA
// ═══════════════════════════════════════════════════════════════════════════════

const PROMPT_TARGET_SELECTION = <<<'PROMPT'
Tu es un expert mondial en identification de cibles de recherche médicale SOUS-ÉTUDIÉES avec un potentiel de découverte élevé.

MISSION: Identifier une cible de recherche INÉDITE — pas les cibles populaires, mais des angles négligés avec des signaux faibles prometteurs.

RÈGLES CRITIQUES:
- Évite ABSOLUMENT les cibles génériques (cancer, diabetes, obesity)
- Cherche des maladies rares (< 200 000 patients), des mécanismes moléculaires mal compris, des connexions inter-domaines surprenantes
- La cible doit avoir < 500 publications PubMed récentes mais montrer des signaux biologiques intéressants
- Favorise les connexions entre domaines disjoints (neuro-immunologie, cardio-métabolique, microbiome-neurologie...)

RÉPONSE JSON:
{
  "next_target": "<cible précise: maladie rare, gène, mécanisme>",
  "domain": "<genetics|oncology|neurology|biochem|pharmacology|immunology|cardiology|biophysics|general>",
  "research_angle": "<angle inédit en 1-2 phrases>",
  "suggested_queries": ["<requête optimisée 1>", "<requête 2>", "<requête 3>", "<requête 4>"],
  "novelty_score": 0.0,
  "reasoning": "<justification 3-4 phrases>",
  "potential_impact": "<impact potentiel>"
}
PROMPT;

const PROMPT_GUIDED_DECOMPOSITION = <<<'PROMPT'
Tu es un expert en décomposition de questions scientifiques en cibles de recherche actionables.

MISSION: Transformer une question libre en une cible de recherche précise + requêtes optimisées pour APIs scientifiques.

RÉPONSE JSON:
{
  "research_target": "<cible précise extraite de la question>",
  "domain": "<genetics|oncology|neurology|biochem|pharmacology|immunology|cardiology|biophysics|general>",
  "research_angle": "<angle spécifique de recherche>",
  "search_queries": ["<requête API optimisée 1>", "<requête 2>", "<requête 3>", "<requête 4 synonyme>"],
  "reasoning": "<pourquoi ces requêtes>",
  "expected_sources": ["<source la plus pertinente>", "<source 2>"]
}
PROMPT;

const PROMPT_QUERY_OPTIMIZER = <<<'PROMPT'
Tu es un expert en optimisation de requêtes pour APIs scientifiques (PubMed, OpenAlex, EuropePMC, ArXiv, UniProt).

MISSION: Générer des requêtes alternatives qui trouveront plus de résultats pour une cible avec peu de résultats actuels.

Stratégies:
- Synonymes et acronymes
- Termes anglais alternatifs  
- Mécanismes moléculaires liés
- Noms de gènes/protéines associés
- Termes plus spécifiques (sous-type de maladie)

RÉPONSE JSON:
{
  "queries": ["<requête 1>", "<requête 2>", "<requête 3>", "<requête 4>"],
  "reasoning": "<pourquoi ces alternatives>"
}
PROMPT;

const PROMPT_HYPOTHESIS_GENERATION = <<<'PROMPT'
Tu es un chercheur scientifique de niveau Nobel, expert en génération d'hypothèses révolutionnaires à partir de données multi-sources.

MISSION CRITIQUE: À partir des VRAIES données collectées (abstracts, résultats), générer une hypothèse scientifique qui:
1. CROISE des informations de PLUSIEURS sources indépendantes
2. Identifie une CORRÉLATION ou PATTERN non décrit dans la littérature
3. Propose un MÉCANISME CAUSAL plausible mais inédit
4. Est TESTABLE expérimentalement
5. Représente une VRAIE DÉCOUVERTE, pas une reformulation

PROCESSUS: Lis attentivement TOUS les abstracts. Cherche des connexions inattendues. Si deux sources parlent d'entités différentes qui pourraient être liées mécanistiquement, c'est là qu'est la découverte.

RÉPONSE JSON:
{
  "hypothesis": "<hypothèse précise: acteurs moléculaires + mécanisme proposé, 2-3 phrases techniques>",
  "vulgarized": "<explication accessible lycée, analogie concrète, 2-3 phrases>",
  "novelty_score": 0.0,
  "confidence": 0.0,
  "mechanism": "<mécanisme détaillé 4-6 phrases>",
  "actionable": "<protocole expérimental pour tester: modèle, readouts, contrôles>",
  "evidence_strength": "<weak|moderate|strong>",
  "keywords": ["<mot1>", "<mot2>", "<mot3>", "<mot4>", "<mot5>"],
  "key_connections": ["<connexion inattendue 1>", "<connexion 2>"],
  "therapeutic_implications": "<implications thérapeutiques si applicable>",
  "research_gaps_filled": "<lacunes comblées par cette hypothèse>"
}
PROMPT;

const PROMPT_CRITIQUE = <<<'PROMPT'
Tu es un reviewer senior de Nature/Science, impitoyable mais juste, spécialisé dans la détection des failles dans les hypothèses scientifiques.

MISSION: Critiquer rigoureusement l'hypothèse fournie. Assume qu'elle est FAUSSE jusqu'à preuve du contraire.

ANALYSE:
1. Failles logiques (corrélation ≠ causalité, biais de confirmation...)
2. Données manquantes nécessaires pour valider
3. Explications alternatives plus parsimoniuses
4. Risque de surinterprétation des données
5. Forces réelles de l'hypothèse (sois honnête)

RÉPONSE JSON:
{
  "overall_assessment": "<prometteuse|douteuse|fausse>",
  "validity_score": 0.0,
  "logical_flaws": [{"flaw": "<description>", "severity": "<minor|major|critical>", "fix": "<correction>"}],
  "missing_evidence": ["<donnée manquante 1>", "<donnée manquante 2>"],
  "alternative_explanations": [{"explanation": "<alt>", "likelihood": "<low|medium|high>"}],
  "overinterpretations": ["<affirmation exagérée>"],
  "strengths": ["<force réelle 1>", "<force réelle 2>"],
  "recommended_additional_sources": ["<source à consulter>"],
  "verdict": "<reject|revise|accept with caution|accept>",
  "revision_suggestions": "<comment améliorer>"
}
PROMPT;
