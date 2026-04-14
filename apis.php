<?php
/**
 * DISCOVERY ENGINE — apis.php
 * Fonctions API avec vrais abstracts + requêtes multiples adaptatives
 */

if (!defined('DE_VERSION')) require_once __DIR__ . '/config.php';

// ── PUBMED : recherche + fetch abstracts réels ────────────────────────────────
function api_pubmed(string $query, int $max = 10): array {
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term="
         . urlencode($query) . "&retmode=json&retmax={$max}&sort=relevance";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('PubMed', $query, $r['error']);

    $d   = @json_decode($r['data'], true);
    $ids = $d['esearchresult']['idlist'] ?? [];
    $total = (int)($d['esearchresult']['count'] ?? 0);
    if (empty($ids)) return _empty('PubMed', $query, 'no ids', $total);

    // Fetch abstracts réels
    $fetch_url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id="
               . implode(',', array_slice($ids, 0, 6)) . "&retmode=xml&rettype=abstract";
    $fr = shu_curl($fetch_url, null, ['Accept: application/xml'], 40);
    $abstracts = '';
    $items = [];

    if ($fr['success']) {
        @preg_match_all('/<ArticleTitle>(.*?)<\/ArticleTitle>/s', $fr['data'], $titles);
        @preg_match_all('/<AbstractText[^>]*>(.*?)<\/AbstractText>/s', $fr['data'], $abst);
        @preg_match_all('/<PMID[^>]*>(\d+)<\/PMID>/s', $fr['data'], $pmids);

        $pmids_list = array_unique($pmids[1] ?? []);
        foreach (array_slice($pmids_list, 0, 6) as $i => $pmid) {
            $title = strip_tags($titles[1][$i] ?? '');
            $abs   = strip_tags($abst[1][$i] ?? '');
            if ($title) {
                $items[] = ['id'=>$pmid,'title'=>$title,'url'=>"https://pubmed.ncbi.nlm.nih.gov/$pmid/",'abstract'=>substr($abs,0,400)];
                $abstracts .= "• {$title}\n";
                if ($abs) $abstracts .= "  Abstract: " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
            }
        }
    }

    return ['source'=>'PubMed','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── EUROPEPMC : full-text search ──────────────────────────────────────────────
function api_europepmc(string $query, int $max = 8): array {
    $url = "https://www.ebi.ac.uk/europepmc/webservices/rest/search?query="
         . urlencode($query) . "&resultType=lite&pageSize={$max}&format=json&sort=relevance";
    $r = shu_curl($url, null, [], 40);
    if (!$r['success']) return _empty('EuropePMC', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['resultList']['result'] ?? [];
    $total   = (int)($d['hitCount'] ?? count($results));
    $abstracts = ''; $items = [];

    foreach (array_slice($results, 0, 6) as $p) {
        $title = $p['title'] ?? '';
        $abs   = $p['abstractText'] ?? '';
        if ($title) {
            $items[] = ['id'=>$p['pmid']??$p['id']??'','title'=>substr($title,0,120),'abstract'=>substr($abs,0,400)];
            $abstracts .= "• {$title}\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'EuropePMC','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── OPENALEX : 250M publications ──────────────────────────────────────────────
function api_openalex(string $query, int $max = 8): array {
    $url = "https://api.openalex.org/works?search=" . urlencode($query)
         . "&per-page={$max}&sort=relevance_score:desc&mailto=research@discovery.local";
    $r = shu_curl($url, null, [], 40);
    if (!$r['success']) return _empty('OpenAlex', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['results'] ?? [];
    $total   = (int)($d['meta']['count'] ?? count($results));
    $abstracts = ''; $items = [];

    foreach (array_slice($results, 0, 6) as $p) {
        $title = $p['title'] ?? '';
        // OpenAlex encode l'abstract en "inverted index" — on récupère la version reconstituée
        $abs = '';
        if (!empty($p['abstract_inverted_index'])) {
            $words = []; $max_pos = 0;
            foreach ($p['abstract_inverted_index'] as $word => $positions) {
                foreach ($positions as $pos) { $words[$pos] = $word; $max_pos = max($max_pos,$pos); }
            }
            ksort($words); $abs = implode(' ', $words);
        }
        if ($title) {
            $items[] = ['id'=>$p['id']??'','title'=>substr($title,0,120),'abstract'=>substr($abs,0,400),'year'=>$p['publication_year']??'','cited'=>$p['cited_by_count']??0];
            $abstracts .= "• {$title}" . ($p['publication_year'] ? " ({$p['publication_year']})" : "") . " [Cités: ".($p['cited_by_count']??0)."]\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'OpenAlex','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── CROSSREF : metadata DOI ───────────────────────────────────────────────────
function api_crossref(string $query, int $max = 8): array {
    $url = "https://api.crossref.org/works?query=" . urlencode($query)
         . "&rows={$max}&sort=relevance&select=title,abstract,author,published,DOI,is-referenced-by-count";
    $r = shu_curl($url, null, ['User-Agent: DiscoveryEngine/2.0 (mailto:research@discovery.local)'], 35);
    if (!$r['success']) return _empty('CrossRef', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['message']['items'] ?? [];
    $total   = (int)($d['message']['total-results'] ?? count($results));
    $abstracts = ''; $items = [];

    foreach (array_slice($results, 0, 6) as $p) {
        $title = is_array($p['title']) ? ($p['title'][0] ?? '') : ($p['title'] ?? '');
        $abs   = $p['abstract'] ?? '';
        $abs   = strip_tags($abs);
        if ($title) {
            $items[] = ['id'=>$p['DOI']??'','title'=>substr($title,0,120),'abstract'=>substr($abs,0,400),'cited'=>$p['is-referenced-by-count']??0];
            $abstracts .= "• {$title} [DOI: " . ($p['DOI']??'') . "]\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'CrossRef','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── ARXIV : préprints ─────────────────────────────────────────────────────────
function api_arxiv(string $query, int $max = 6): array {
    $q = str_replace(' ', '+', $query);
    $url = "https://export.arxiv.org/api/query?search_query=all:{$q}&max_results={$max}&sortBy=relevance";
    $r = shu_curl($url, null, [], 55);
    if (!$r['success']) return _empty('ArXiv', $query, $r['error']);

    @preg_match_all('/<entry>(.*?)<\/entry>/s', $r['data'], $entries);
    $items = []; $abstracts = '';
    foreach (array_slice($entries[1] ?? [], 0, 6) as $entry) {
        @preg_match('/<title>(.*?)<\/title>/s', $entry, $t);
        @preg_match('/<summary>(.*?)<\/summary>/s', $entry, $s);
        @preg_match('/<id>(.*?)<\/id>/s', $entry, $idm);
        $title = trim(str_replace("\n",' ',$t[1]??''));
        $abs   = trim(str_replace("\n",' ',$s[1]??''));
        if ($title) {
            $items[] = ['id'=>basename($idm[1]??'#'),'title'=>substr($title,0,120),'abstract'=>substr($abs,0,400),'url'=>$idm[1]??'#'];
            $abstracts .= "• {$title}\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    $total = count($items);
    @preg_match('/<opensearch:totalResults[^>]*>(\d+)/', $r['data'], $tot);
    if ($tot[1] ?? 0) $total = (int)$tot[1];

    return ['source'=>'ArXiv','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── SEMANTIC SCHOLAR : AI-focused ─────────────────────────────────────────────
function api_semantic_scholar(string $query, int $max = 8): array {
    $url = "https://api.semanticscholar.org/graph/v1/paper/search?query="
         . urlencode($query) . "&limit={$max}&fields=title,abstract,year,citationCount,influentialCitationCount";
    $r = shu_curl($url, null, [], 40);
    if (!$r['success']) return _empty('SemanticScholar', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['data'] ?? [];
    $total   = (int)($d['total'] ?? count($results));
    $abstracts = ''; $items = [];

    foreach (array_slice($results, 0, 6) as $p) {
        $title = $p['title'] ?? '';
        $abs   = $p['abstract'] ?? '';
        if ($title) {
            $items[] = ['id'=>$p['paperId']??'','title'=>substr($title,0,120),'abstract'=>substr($abs,0,400),'year'=>$p['year']??'','cited'=>$p['citationCount']??0];
            $abstracts .= "• {$title}" . ($p['year']?" ({$p['year']})":'') . " [Cités: ".($p['citationCount']??0)."]\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'SemanticScholar','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── UNIPROT ───────────────────────────────────────────────────────────────────
function api_uniprot(string $query, int $max = 6): array {
    $url = "https://rest.uniprot.org/uniprotkb/search?query=" . urlencode($query)
         . "+AND+reviewed:true&format=json&size={$max}&fields=id,gene_names,protein_name,organism_name,function";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('UniProt', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['results'] ?? [];
    $total   = count($results);
    $abstracts = ''; $items = [];

    foreach (array_slice($results, 0, 6) as $p) {
        $name   = $p['uniProtkbId'] ?? '';
        $pname  = $p['proteinDescription']['recommendedName']['fullName']['value']
               ?? $p['proteinDescription']['submittedName'][0]['fullName']['value'] ?? '';
        $func   = $p['comments'][0]['texts'][0]['value'] ?? '';
        $genes  = implode(', ', array_slice(array_column($p['genes'][0]['geneName'] ?? [], 'value'), 0, 3));
        if ($name) {
            $items[] = ['id'=>$name,'name'=>$pname,'genes'=>$genes,'function'=>substr($func,0,300)];
            $abstracts .= "• Protein: {$name} | {$pname}" . ($genes?" | Gene: {$genes}":'') . "\n";
            if ($func) $abstracts .= "  Function: " . substr($func, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'UniProt','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── CLINVAR : variants génétiques ─────────────────────────────────────────────
function api_clinvar(string $query, int $max = 8): array {
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=clinvar&term="
         . urlencode($query) . "&retmode=json&retmax={$max}";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('ClinVar', $query, $r['error']);

    $d    = @json_decode($r['data'], true);
    $ids  = $d['esearchresult']['idlist'] ?? [];
    $total= (int)($d['esearchresult']['count'] ?? 0);
    if (empty($ids)) return _empty('ClinVar', $query, 'no results', $total);

    $fetch_url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=clinvar&id="
               . implode(',', array_slice($ids,0,5)) . "&retmode=json";
    $fr = shu_curl($fetch_url, null, [], 35);
    $abstracts = ''; $items = [];

    if ($fr['success']) {
        $fd = @json_decode($fr['data'], true);
        $result_set = $fd['result'] ?? [];
        foreach ($result_set as $vid => $v) {
            if ($vid === 'uids') continue;
            $title   = $v['title'] ?? '';
            $clin_sig = $v['clinical_significance']['description'] ?? '';
            $condition = $v['trait_set'][0]['trait_name'] ?? '';
            if ($title) {
                $items[]    = ['id'=>$vid,'title'=>$title,'significance'=>$clin_sig,'condition'=>$condition];
                $abstracts .= "• ClinVar {$vid}: {$title} | Significance: {$clin_sig} | Condition: {$condition}\n";
            }
        }
    }
    return ['source'=>'ClinVar','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── KEGG : pathways ───────────────────────────────────────────────────────────
function api_kegg(string $query, int $max = 6): array {
    $url = "https://rest.kegg.jp/find/pathway/" . urlencode($query);
    $r = shu_curl($url, null, ['Accept: text/plain'], 30);
    if (!$r['success']) return _empty('KEGG', $query, $r['error']);

    $lines = explode("\n", trim($r['data']));
    $items = []; $abstracts = '';
    foreach (array_slice($lines, 0, $max) as $line) {
        if (!$line) continue;
        [$id, $name] = explode("\t", $line . "\t") + [0 => '', 1 => ''];
        if ($id) {
            $items[]    = ['id'=>$id,'name'=>trim($name)];
            $abstracts .= "• Pathway {$id}: " . trim($name) . "\n";
        }
    }
    return ['source'=>'KEGG','query'=>$query,'hits'=>count($items),'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── DISGENET : gènes-maladies ─────────────────────────────────────────────────
function api_disgenet(string $query, int $max = 6): array {
    $url = "https://www.disgenet.org/api/gda/disease/" . urlencode($query)
         . "?format=json&limit={$max}";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('DisGeNET', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    if (!is_array($d)) return _empty('DisGeNET', $query, 'parse error');
    $results = is_array($d[0] ?? null) ? $d : [];
    $abstracts = ''; $items = [];
    foreach (array_slice($results, 0, $max) as $g) {
        $gene  = $g['gene_symbol'] ?? '';
        $score = $g['score'] ?? '';
        $pmids = $g['pmid_count'] ?? '';
        if ($gene) {
            $items[]    = ['gene'=>$gene,'score'=>$score,'pmids'=>$pmids];
            $abstracts .= "• Gene: {$gene} | GDA score: {$score} | {$pmids} publications\n";
        }
    }
    return ['source'=>'DisGeNET','query'=>$query,'hits'=>count($items),'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── ZENODO : datasets + codes ─────────────────────────────────────────────────
function api_zenodo(string $query, int $max = 6): array {
    $url = "https://zenodo.org/api/records?q=" . urlencode($query)
         . "&size={$max}&sort=mostrecent&type=dataset,software";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('Zenodo', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['hits']['hits'] ?? [];
    $total   = (int)($d['hits']['total'] ?? count($results));
    $abstracts = ''; $items = [];
    foreach (array_slice($results, 0, 6) as $rec) {
        $title = $rec['metadata']['title'] ?? '';
        $desc  = strip_tags($rec['metadata']['description'] ?? '');
        $type  = $rec['metadata']['resource_type']['type'] ?? '';
        if ($title) {
            $items[]    = ['id'=>$rec['id']??'','title'=>substr($title,0,120),'type'=>$type];
            $abstracts .= "• [{$type}] {$title}\n";
            if ($desc) $abstracts .= "  " . substr($desc, 0, 300) . "\n\n";
        }
    }
    return ['source'=>'Zenodo','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── CORE : open access ────────────────────────────────────────────────────────
function api_core(string $query, int $max = 6): array {
    $url = "https://api.core.ac.uk/v3/search/works?q=" . urlencode($query)
         . "&limit={$max}&scroll=false";
    $r = shu_curl($url, null, [], 40);
    if (!$r['success']) return _empty('CORE', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['results'] ?? [];
    $total   = (int)($d['totalHits'] ?? count($results));
    $abstracts = ''; $items = [];
    foreach (array_slice($results, 0, 6) as $p) {
        $title = $p['title'] ?? '';
        $abs   = $p['abstract'] ?? '';
        if ($title) {
            $items[]    = ['id'=>$p['id']??'','title'=>substr($title,0,120),'abstract'=>substr($abs,0,400)];
            $abstracts .= "• {$title}\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'CORE','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── WIKIDATA : contexte général ───────────────────────────────────────────────
function api_wikidata(string $query, int $max = 5): array {
    $url = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search="
         . urlencode($query) . "&language=en&format=json&limit={$max}&type=item";
    $r = shu_curl($url, null, [], 30);
    if (!$r['success']) return _empty('Wikidata', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['search'] ?? [];
    $abstracts = ''; $items = [];
    foreach ($results as $e) {
        $label = $e['label'] ?? '';
        $desc  = $e['description'] ?? '';
        if ($label) {
            $items[]    = ['id'=>$e['id']??'','label'=>$label,'description'=>$desc];
            $abstracts .= "• {$label}: {$desc}\n";
        }
    }
    return ['source'=>'Wikidata','query'=>$query,'hits'=>count($items),'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── INSPREHEP : physique/biophysique ─────────────────────────────────────────
function api_inspirehep(string $query, int $max = 6): array {
    $url = "https://inspirehep.net/api/literature?sort=mostrecent&size={$max}&q=" . urlencode($query)
         . "&fields=titles,abstracts,publication_info,citation_count";
    $r = shu_curl($url, null, [], 35);
    if (!$r['success']) return _empty('INSPIRE-HEP', $query, $r['error']);

    $d = @json_decode($r['data'], true);
    $results = $d['hits']['hits'] ?? [];
    $total   = (int)($d['hits']['total'] ?? count($results));
    $abstracts = ''; $items = [];
    foreach (array_slice($results, 0, 6) as $p) {
        $meta  = $p['metadata'] ?? [];
        $title = $meta['titles'][0]['title'] ?? '';
        $abs   = $meta['abstracts'][0]['value'] ?? '';
        if ($title) {
            $items[]    = ['title'=>substr($title,0,120),'abstract'=>substr($abs,0,400),'cited'=>$meta['citation_count']??0];
            $abstracts .= "• {$title}\n";
            if ($abs) $abstracts .= "  " . substr($abs, 0, ABSTRACT_MAX_CHARS) . "\n\n";
        }
    }
    return ['source'=>'INSPIRE-HEP','query'=>$query,'hits'=>$total,'items'=>$items,'abstracts'=>$abstracts,'success'=>true];
}

// ── HELPER ────────────────────────────────────────────────────────────────────
function _empty(string $source, string $query, string $err = '', int $total = 0): array {
    return ['source'=>$source,'query'=>$query,'hits'=>$total,'items'=>[],'abstracts'=>'','success'=>false,'error'=>$err];
}

// ── DISPATCH : nom → fonction ──────────────────────────────────────────────────
function call_source(string $name, string $query, int $max = 8): array {
    $map = [
        'pubmed'          => 'api_pubmed',
        'europepmc'       => 'api_europepmc',
        'openalex'        => 'api_openalex',
        'crossref'        => 'api_crossref',
        'arxiv'           => 'api_arxiv',
        'semantic_scholar'=> 'api_semantic_scholar',
        'uniprot'         => 'api_uniprot',
        'clinvar'         => 'api_clinvar',
        'kegg'            => 'api_kegg',
        'disgenet'        => 'api_disgenet',
        'zenodo'          => 'api_zenodo',
        'core'            => 'api_core',
        'wikidata'        => 'api_wikidata',
        'inspirehep'      => 'api_inspirehep',
    ];
    $fn = $map[$name] ?? null;
    if (!$fn) return _empty($name, $query, 'unknown source');
    return $fn($query, $max);
}

// ── SÉLECTION DES SOURCES SELON DOMAINE ───────────────────────────────────────
function get_sources_for_domain(string $domain): array {
    $map = [
        'genetics'     => ['pubmed','europepmc','clinvar','uniprot','openalex','disgenet','kegg','crossref'],
        'oncology'     => ['pubmed','europepmc','openalex','disgenet','clinvar','crossref','semantic_scholar','zenodo'],
        'neurology'    => ['pubmed','arxiv','europepmc','openalex','semantic_scholar','crossref','kegg','zenodo'],
        'biochem'      => ['uniprot','pubmed','europepmc','kegg','crossref','openalex','zenodo','inspirehep'],
        'pharmacology' => ['pubmed','europepmc','crossref','openalex','semantic_scholar','kegg','zenodo','core'],
        'immunology'   => ['pubmed','europepmc','uniprot','openalex','crossref','kegg','disgenet','arxiv'],
        'cardiology'   => ['pubmed','europepmc','clinvar','openalex','crossref','disgenet','kegg','semantic_scholar'],
        'biophysics'   => ['arxiv','pubmed','europepmc','inspirehep','openalex','uniprot','zenodo','crossref'],
        'general'      => ['pubmed','europepmc','openalex','arxiv','crossref','semantic_scholar','uniprot','zenodo'],
    ];
    return $map[$domain] ?? $map['general'];
}
