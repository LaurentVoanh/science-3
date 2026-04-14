<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  DISCOVERY ENGINE ULTIMATE — CONFIG.PHP                              ║
 * ║  Moteur de Découverte Autonome • Boucles de réévaluation • 36 APIs   ║
 * ║  Basé sur Science Hub Ultimate - Amélioré pour vraies découvertes    ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

// ============================================================================
// PROTECTIONS & INITIALISATION
// ============================================================================
@error_reporting(E_ALL);
@ini_set('display_errors', 0);
@ini_set('log_errors', 1);
@ini_set('error_log', __DIR__ . '/storage/php_errors.log');
// Ne pas nettoyer les buffers ici, cela sera fait dans engine.php si nécessaire

// ============================================================================
// CONSTANTES GLOBALES
// ============================================================================
defined('DISCOVERY_VERSION') or define('DISCOVERY_VERSION', '2.0.0-discovery');
// Alias pour compatibilité avec engine.php et apis.php
defined('DE_VERSION') or define('DE_VERSION', DISCOVERY_VERSION);
defined('ABSTRACT_MAX_CHARS') or define('ABSTRACT_MAX_CHARS', 600);
defined('STORAGE_PATH')        or define('STORAGE_PATH',       __DIR__ . '/storage/');
defined('DB_PATH')             or define('DB_PATH',            __DIR__ . '/discovery.sqlite');
defined('MAX_STEP_TIME')       or define('MAX_STEP_TIME',      25);
defined('MAX_LOOPS_BEFORE_EVAL') or define('MAX_LOOPS_BEFORE_EVAL', 5);
defined('MIN_DISCOVERY_SCORE') or define('MIN_DISCOVERY_SCORE', 0.75);
defined('MAX_QUERIES_PER_LOOP') or define('MAX_QUERIES_PER_LOOP', 50);
defined('MAX_ERRORS_BEFORE_RESET')  or define('MAX_ERRORS_BEFORE_RESET', 10);

// Création des dossiers de stockage si nécessaires
if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}
if (!is_dir(STORAGE_PATH . 'state')) {
    @mkdir(STORAGE_PATH . 'state', 0755, true);
}

// ============================================================================
// CLÉS API MISTRAL — Rotation automatique avec fallback
// ⚠️ REMPLACEZ PAR VOS CLÉS RÉELLES EN PRODUCTION
// ============================================================================

// ============================================================================
// CLÉS API MISTRAL — Rotation automatique avec fallback
// ⚠️ REMPLACEZ PAR VOS CLÉS RÉELLES EN PRODUCTION
// ============================================================================
$MISTRAL_KEYS = [
    'z5qaRTjWUjGJpAk5z35XcdEP5ZbH8Rakea',
    'ao3rG1zvdq1yDOvjb7Z4J3J3eHXRShytuz',
    'zvEzQMKN74Ez8RIwJ6y8J30ENDjFruXkFa'
];





$MISTRAL_KEY_INDEX = 0;

$MISTRAL_CONFIG = [
    'keys'              => $MISTRAL_KEYS,
    'current_index'     => 0,
    'emergency_model'   => 'mistral-small-2506',
    'models_available'  => [
        'pixtral'  => ['name' => 'pixtral-12b-2409',   'tokens_max' => 128000, 'use_for' => 'vision,analyse_images,quick_tasks'],
        'devstral' => ['name' => 'devstral-2512',    'tokens_max' => 256000, 'use_for' => 'code,development,debugging'],
        'large'    => ['name' => 'mistral-large-2512','tokens_max' => 256000, 'use_for' => 'deep_research,synthesis,critique'],
        'medium'   => ['name' => 'mistral-large-2512','tokens_max' => 256000, 'use_for' => 'synthesis,article_generation'],
        'small'    => ['name' => 'mistral-small-2506', 'tokens_max' => 256000, 'use_for' => 'target_selection,quick_tasks'],
    ],
    'default_model'     => 'mistral-small-2506',
    'deep_model'        => 'mistral-large-2512',
    'critique_model'    => 'mistral-large-2512',
];



// ============================================================================
// 36 SOURCES SCIENTIFIQUES — URLs validées + descriptions pour l'IA
// ============================================================================
$SCIENTIFIC_APIS = [
    // ─── LITTÉRATURE BIOMÉDICALE (8 sources) ────────────────────────────
    'pubmed' => [
        'name'         => 'PubMed',
        'emoji'        => '📗',
        'color'        => '#0066cc',
        'base'         => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
        'max_standard' => 8,
        'max_deep'     => 20,
        'timeout'      => 35,
        'type'         => 'biomedical',
        'weight'       => 1.5,
        'desc'         => 'Base de données biomédicale principale NCBI',
        'query_note'   => 'Terme de recherche PubMed. Ex: "myocarditis mRNA vaccine" ou "BRCA1 cancer 2023"',
    ],
    'europepmc' => [
        'name'         => 'EuropePMC',
        'emoji'        => '🌍',
        'color'        => '#0077bb',
        'base'         => 'https://www.ebi.ac.uk/europepmc/webservices/rest/search',
        'max_standard' => 8,
        'max_deep'     => 20,
        'timeout'      => 40,
        'type'         => 'biomedical',
        'weight'       => 1.3,
        'desc'         => 'Europe PubMed Central — texte intégral annoté',
        'query_note'   => 'Recherche libre en anglais. Ex: "COVID long term neurological"',
    ],
    'openalex' => [
        'name'         => 'OpenAlex',
        'emoji'        => '🌐',
        'color'        => '#8b5cf6',
        'base'         => 'https://api.openalex.org/works',
        'max_standard' => 8,
        'max_deep'     => 20,
        'timeout'      => 40,
        'type'         => 'cross-domain',
        'weight'       => 1.2,
        'desc'         => '250M+ publications avec graphe de citations',
        'query_note'   => 'Terme de recherche en anglais. Ex: "alzheimer biomarkers"',
    ],
    'crossref' => [
        'name'         => 'CrossRef',
        'emoji'        => '📄',
        'color'        => '#f59e0b',
        'base'         => 'https://api.crossref.org/works',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 35,
        'type'         => 'literature',
        'weight'       => 1.1,
        'desc'         => 'Métadonnées DOI — CrossRef',
        'query_note'   => 'Recherche libre. Ex: "insulin resistance type 2 diabetes"',
    ],
    'arxiv' => [
        'name'         => 'ArXiv',
        'emoji'        => '📐',
        'color'        => '#ff6600',
        'base'         => 'https://export.arxiv.org/api/query',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 60,
        'type'         => 'preprint',
        'weight'       => 1.0,
        'desc'         => 'Préprints scientifiques (biologie, physique, IA)',
        'query_note'   => 'Termes en anglais sans guillemets. Ex: CRISPR+genome+editing',
    ],
    'zenodo' => [
        'name'         => 'Zenodo',
        'emoji'        => '🏛️',
        'color'        => '#14b8a6',
        'base'         => 'https://zenodo.org/api/records',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 35,
        'type'         => 'data',
        'weight'       => 0.9,
        'desc'         => 'Dépôt de datasets, codes et publications',
        'query_note'   => 'Recherche libre. Ex: "genomics dataset RNA-seq"',
    ],
    'inspirehep' => [
        'name'         => 'INSPIRE-HEP',
        'emoji'        => '⚛️',
        'color'        => '#ec4899',
        'base'         => 'https://inspirehep.net/api/literature',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'physics',
        'weight'       => 0.8,
        'desc'         => 'Physique des hautes énergies et biophysique',
        'query_note'   => 'Terme en anglais. Ex: "biophysics protein structure"',
    ],
    'datacite' => [
        'name'         => 'DataCite',
        'emoji'        => '📊',
        'color'        => '#06b6d4',
        'base'         => 'https://api.datacite.org/works',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'data',
        'weight'       => 0.8,
        'desc'         => 'Datasets avec DOI — DataCite',
        'query_note'   => 'Recherche de datasets scientifiques. Ex: "genomics cancer dataset"',
    ],

    // ─── GÉNÉTIQUE & PROTÉINES (6 sources) ──────────────────────────────
    'uniprot' => [
        'name'         => 'UniProt',
        'emoji'        => '🔵',
        'color'        => '#00aa55',
        'base'         => 'https://rest.uniprot.org/uniprotkb/search',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 35,
        'type'         => 'protein',
        'weight'       => 1.4,
        'desc'         => 'Base de données universelle des protéines',
        'query_note'   => 'Nom de gène ou protéine. Ex: TP53, BRCA1, insulin',
    ],
    'ensembl' => [
        'name'         => 'Ensembl',
        'emoji'        => '🧬',
        'color'        => '#dc2626',
        'base'         => 'https://rest.ensembl.org/lookup/symbol/homo_sapiens',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 30,
        'type'         => 'genomics',
        'weight'       => 1.3,
        'desc'         => 'Génomique — coordonnées chromosomiques',
        'query_note'   => 'NOM exact du gène humain (symbole officiel HGNC). Ex: BRCA1, TP53',
    ],
    'clinvar' => [
        'name'         => 'ClinVar',
        'emoji'        => '🏥',
        'color'        => '#cc2200',
        'base'         => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 35,
        'type'         => 'genetics',
        'weight'       => 1.3,
        'desc'         => 'Variants génétiques cliniques NCBI',
        'query_note'   => 'Terme de recherche clinvar. Ex: "BRCA1 pathogenic"',
    ],
    'geo' => [
        'name'         => 'GEO',
        'emoji'        => '📈',
        'color'        => '#7c3aed',
        'base'         => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'genomics',
        'weight'       => 1.2,
        'desc'         => 'Gene Expression Omnibus — données d\'expression',
        'query_note'   => 'Terme de recherche GEO. Ex: "myocarditis RNA-seq"',
    ],
    'arrayexpress' => [
        'name'         => 'ArrayExpress',
        'emoji'        => '🔬',
        'color'        => '#0891b2',
        'base'         => 'https://www.ebi.ac.uk/biostudies/api/v1/search',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'genomics',
        'weight'       => 1.1,
        'desc'         => 'Données d\'expression génique EBI',
        'query_note'   => 'Recherche libre. Ex: "heart inflammation transcriptomics"',
    ],
    'stringdb' => [
        'name'         => 'StringDB',
        'emoji'        => '🕸️',
        'color'        => '#f97316',
        'base'         => 'https://string-db.org/api/json/network',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 40,
        'type'         => 'network',
        'weight'       => 1.2,
        'desc'         => 'Réseau d\'interactions protéines STRING',
        'query_note'   => 'NOM d\'une PROTÉINE (symbole de gène humain). Ex: TP53, MYH7',
    ],

    // ─── CHIMIE & PHARMACOLOGIE (4 sources) ─────────────────────────────
    'chembl' => [
        'name'         => 'ChEMBL',
        'emoji'        => '⚗️',
        'color'        => '#d97706',
        'base'         => 'https://www.ebi.ac.uk/chembl/api/data/',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 40,
        'type'         => 'chemistry',
        'weight'       => 1.2,
        'desc'         => 'Base de données pharmacologique ChEMBL',
        'query_note'   => 'NOM d\'une molécule ou médicament. Ex: ibuprofen, aspirin',
    ],
    'pubchem' => [
        'name'         => 'PubChem',
        'emoji'        => '🧪',
        'color'        => '#059669',
        'base'         => 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'chemistry',
        'weight'       => 1.1,
        'desc'         => 'Propriétés chimiques PubChem',
        'query_note'   => 'NOM d\'un composé chimique. Ex: aspirin, glucose',
    ],
    'kegg' => [
        'name'         => 'KEGG',
        'emoji'        => '🔗',
        'color'        => '#6366f1',
        'base'         => 'https://rest.kegg.jp/find/hsa',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'pathway',
        'weight'       => 1.0,
        'desc'         => 'Voies métaboliques humaines KEGG',
        'query_note'   => 'Terme KEGG (gène ou maladie). Ex: myocarditis, cardiac',
    ],
    'reactome' => [
        'name'         => 'Reactome',
        'emoji'        => '🔄',
        'color'        => '#10b981',
        'base'         => 'https://reactome.org/ContentService/data/pathways/low/entity',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 35,
        'type'         => 'pathway',
        'weight'       => 1.0,
        'desc'         => 'Voies de signalisation Reactome',
        'query_note'   => 'ACCESSION UniProt d\'une protéine. Ex: P04637 (TP53)',
    ],

    // ─── ONTOLOGIES & MALADIES (4 sources) ──────────────────────────────
    'geneontology' => [
        'name'         => 'GeneOntology',
        'emoji'        => '📋',
        'color'        => '#8b5cf6',
        'base'         => 'https://api.geneontology.org/api/search/entity/autocomplete',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'ontology',
        'weight'       => 0.9,
        'desc'         => 'Ontologie des fonctions géniques GO',
        'query_note'   => 'Terme biologique en anglais. Ex: cardiac muscle contraction',
    ],
    'disgenet' => [
        'name'         => 'DisGeNET',
        'emoji'        => '🦠',
        'color'        => '#ef4444',
        'base'         => 'https://www.disgenet.org/api/gda/disease',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 35,
        'type'         => 'disease',
        'weight'       => 1.1,
        'desc'         => 'Associations gènes-maladies DisGeNET',
        'query_note'   => 'NOM de la maladie (en anglais). Ex: myocarditis, cardiomyopathy',
    ],
    'omim' => [
        'name'         => 'OMIM',
        'emoji'        => '🧾',
        'color'        => '#f59e0b',
        'base'         => 'https://api.omim.org/api/entry',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 35,
        'type'         => 'genetics',
        'weight'       => 1.2,
        'desc'         => 'Online Mendelian Inheritance in Man',
        'query_note'   => 'Terme de recherche OMIM. Ex: "cystic fibrosis"',
    ],
    'orphanet' => [
        'name'         => 'Orphanet',
        'emoji'        => '🏥',
        'color'        => '#14b8a6',
        'base'         => 'https://www.orpha.net/ords/orphanet-api/api',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 35,
        'type'         => 'rare_disease',
        'weight'       => 1.0,
        'desc'         => 'Maladies rares et médicaments orphelins',
        'query_note'   => 'Nom de maladie rare. Ex: "progeria", "huntington"',
    ],

    // ─── CONNAISSANCE GÉNÉRALE (4 sources) ──────────────────────────────
    'wikidata' => [
        'name'         => 'Wikidata',
        'emoji'        => '📚',
        'color'        => '#666666',
        'base'         => 'https://www.wikidata.org/w/api.php',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'knowledge',
        'weight'       => 0.7,
        'desc'         => 'Base de connaissances Wikidata',
        'query_note'   => 'Terme de recherche général. Ex: "CRISPR Cas9"',
    ],
    'wikipedia' => [
        'name'         => 'Wikipedia',
        'emoji'        => '📖',
        'color'        => '#333333',
        'base'         => 'https://en.wikipedia.org/w/api.php',
        'max_standard' => 5,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'knowledge',
        'weight'       => 0.6,
        'desc'         => 'Encyclopédie Wikipedia anglaise',
        'query_note'   => 'Terme général. Ex: "quantum computing"',
    ],
    'semanticscholar' => [
        'name'         => 'SemanticScholar',
        'emoji'        => '🎓',
        'color'        => '#168ee6',
        'base'         => 'https://api.semanticscholar.org/graph/v1/paper/search',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 40,
        'type'         => 'literature',
        'weight'       => 1.3,
        'desc'         => 'Moteur de recherche sémantique IA',
        'query_note'   => 'Terme de recherche académique. Ex: "neural networks attention mechanism"',
    ],
    'core' => [
        'name'         => 'CORE',
        'emoji'        => '🌟',
        'color'        => '#00cc66',
        'base'         => 'https://core.ac.uk/api-v2/articles/search',
        'max_standard' => 6,
        'max_deep'     => 15,
        'timeout'      => 35,
        'type'         => 'repository',
        'weight'       => 1.0,
        'desc'         => 'Agrégateur de publications en accès ouvert',
        'query_note'   => 'Recherche libre. Ex: "machine learning healthcare"',
    ],

    // ─── PRÉPRINTS & ARCHIVES OUVERTES (6 sources) ───────────────────────
    'biorxiv' => [
        'name'         => 'bioRxiv',
        'emoji'        => '🧬',
        'color'        => '#0099cc',
        'base'         => 'https://api.biorxiv.org/details/biorxiv/',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'preprint',
        'weight'       => 1.2,
        'desc'         => 'Préprints en sciences biologiques',
        'query_note'   => 'Terme biologique. Ex: "CRISPR gene editing"',
    ],
    'medrxiv' => [
        'name'         => 'medRxiv',
        'emoji'        => '🏥',
        'color'        => '#cc0033',
        'base'         => 'https://api.biorxiv.org/details/medrxiv/',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'preprint',
        'weight'       => 1.3,
        'desc'         => 'Préprints en sciences médicales',
        'query_note'   => 'Terme médical. Ex: "COVID vaccine efficacy"',
    ],
    'philrxiv' => [
        'name'         => 'PhilSci-Archive',
        'emoji'        => '🤔',
        'color'        => '#9933cc',
        'base'         => 'http://philsci-archive.pitt.edu/cgi/search',
        'max_standard' => 4,
        'max_deep'     => 8,
        'timeout'      => 30,
        'type'         => 'philosophy',
        'weight'       => 0.7,
        'desc'         => 'Archive de philosophie des sciences',
        'query_note'   => 'Concept philosophique. Ex: "scientific realism"',
    ],
    'socarxiv' => [
        'name'         => 'SocArXiv',
        'emoji'        => '👥',
        'color'        => '#cc6600',
        'base'         => 'https://osf.io/preprints/socarxiv/',
        'max_standard' => 4,
        'max_deep'     => 8,
        'timeout'      => 30,
        'type'         => 'social',
        'weight'       => 0.6,
        'desc'         => 'Préprints en sciences sociales',
        'query_note'   => 'Terme sciences sociales. Ex: "social inequality"',
    ],
    'psychrxiv' => [
        'name'         => 'PsyArXiv',
        'emoji'        => '🧠',
        'color'        => '#993366',
        'base'         => 'https://osf.io/preprints/psyarxiv/',
        'max_standard' => 4,
        'max_deep'     => 8,
        'timeout'      => 30,
        'type'         => 'psychology',
        'weight'       => 0.8,
        'desc'         => 'Préprints en psychologie',
        'query_note'   => 'Terme psychologie. Ex: "cognitive bias"',
    ],
    'lawarxiv' => [
        'name'         => 'LawArXiv',
        'emoji'        => '⚖️',
        'color'        => '#336699',
        'base'         => 'https://osf.io/preprints/lawarxiv/',
        'max_standard' => 4,
        'max_deep'     => 8,
        'timeout'      => 30,
        'type'         => 'law',
        'weight'       => 0.5,
        'desc'         => 'Préprints en droit',
        'query_note'   => 'Terme juridique. Ex: "intellectual property"',
    ],

    // ─── SOURCES SPÉCIALISÉES SUPPLÉMENTAIRES (4 sources) ───────────────
    'patents' => [
        'name'         => 'GooglePatents',
        'emoji'        => '💡',
        'color'        => '#ff9900',
        'base'         => 'https://patents.google.com/?q=',
        'max_standard' => 4,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'patents',
        'weight'       => 0.9,
        'desc'         => 'Brevets scientifiques et techniques',
        'query_note'   => 'Technologie ou invention. Ex: "gene therapy vector"',
    ],
    'clinicaltrials' => [
        'name'         => 'ClinicalTrials.gov',
        'emoji'        => '🧪',
        'color'        => '#009999',
        'base'         => 'https://clinicaltrials.gov/api/query/full_study',
        'max_standard' => 5,
        'max_deep'     => 12,
        'timeout'      => 35,
        'type'         => 'clinical',
        'weight'       => 1.4,
        'desc'         => 'Essais cliniques en cours',
        'query_note'   => 'Condition ou traitement. Ex: "diabetes immunotherapy"',
    ],
    'fdadrugs' => [
        'name'         => 'FDA Drugs',
        'emoji'        => '💊',
        'color'        => '#006699',
        'base'         => 'https://api.fda.gov/drug/label.json',
        'max_standard' => 4,
        'max_deep'     => 10,
        'timeout'      => 35,
        'type'         => 'regulatory',
        'weight'       => 1.1,
        'desc'         => 'Informations FDA sur les médicaments',
        'query_note'   => 'Nom de médicament. Ex: "aspirin"',
    ],
    'epo' => [
        'name'         => 'EuropeanPatentOffice',
        'emoji'        => '🇪🇺',
        'color'        => '#003399',
        'base'         => 'https://worldwide.espacenet.com/patent/search',
        'max_standard' => 4,
        'max_deep'     => 10,
        'timeout'      => 30,
        'type'         => 'patents',
        'weight'       => 0.8,
        'desc'         => 'Brevets européens',
        'query_note'   => 'Innovation technique. Ex: "mRNA delivery"',
    ],
];

// ============================================================================
// FONCTION CURL OPTIMISÉE — Plus rapide, plus de retries
// ============================================================================
function shu_curl($url, $post_data = null, $custom_headers = [], $timeout = 45, $max_retries = 5) {
    $attempt    = 0;
    $last_error = null;
    $http_code  = 0;

    while($attempt < $max_retries) {
        $attempt++;
        $ch = @curl_init($url);

        if(!$ch) {
            $last_error = 'curl_init failed';
            continue;
        }

        $headers = array_merge(
            ['Accept: application/json', 'Content-Type: application/json', 'User-Agent: DiscoveryEngine/' . DISCOVERY_VERSION],
            $custom_headers
        );

        @curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'DiscoveryEngine/' . DISCOVERY_VERSION . ' (Autonomous Research Platform)',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => 'gzip,deflate',
        ]);

        if($post_data) {
            @curl_setopt($ch, CURLOPT_POST, true);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($post_data) ? $post_data : @json_encode($post_data));
        }

        $result        = @curl_exec($ch);
        $error         = @curl_error($ch);
        $http_code     = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_time = @curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        @curl_close($ch);

        if($result && !$error && $http_code >= 200 && $http_code < 300) {
            return [
                'success'         => true,
                'data'            => $result,
                'error'           => null,
                'http_code'       => $http_code,
                'attempts'        => $attempt,
                'response_time_ms'=> round($response_time * 1000),
            ];
        }

        $last_error = $error ?: "HTTP $http_code";
        
        // Backoff exponentiel plus agressif pour rate limit
        if($http_code === 429) {
            $wait = 2000000; // 2 secondes
        } else {
            $wait = pow(2, $attempt) * 200000; // 0.2s, 0.4s, 0.8s...
        }
        
        if($attempt < $max_retries) @usleep($wait);
    }

    return [
        'success'   => false,
        'data'      => null,
        'error'     => $last_error,
        'http_code' => $http_code,
        'attempts'  => $attempt,
    ];
}

// ============================================================================
// IA MISTRAL — Appel avec rotation de clé et modèles multiples
// ============================================================================
function shu_mistral($messages, $model = null, $max_tokens = 2000, $temperature = 0.4, $require_json = true) {
    global $MISTRAL_KEYS, $MISTRAL_KEY_INDEX, $MISTRAL_CONFIG;

    if($model === null) {
        $model = $MISTRAL_CONFIG['default_model'];
    }

    $key = $MISTRAL_KEYS[$MISTRAL_KEY_INDEX % count($MISTRAL_KEYS)];
    $MISTRAL_KEY_INDEX++;

    $payload = [
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => $max_tokens,
        'top_p'       => 0.95,
        'safe_prompt' => true,
    ];

    if($require_json) {
        $payload['response_format'] = ['type' => 'json_object'];
    }

    $response = shu_curl(
        'https://api.mistral.ai/v1/chat/completions',
        @json_encode($payload),
        ['Authorization: Bearer ' . $key],
        120,
        3
    );

    if(!$response['success']) {
        // Rotation de clé en cas d'erreur d'authentification ou rate limit
        if(in_array($response['http_code'], [401, 403, 429])) {
            $MISTRAL_KEY_INDEX++;
            $key = $MISTRAL_KEYS[$MISTRAL_KEY_INDEX % count($MISTRAL_KEYS)];
            $response = shu_curl(
                'https://api.mistral.ai/v1/chat/completions',
                @json_encode($payload),
                ['Authorization: Bearer ' . $key],
                120,
                2
            );
        }

        if(!$response['success']) {
            return ['success' => false, 'error' => $response['error'], 'http_code' => $response['http_code']];
        }
    }

    $json = @json_decode($response['data'], true);
    if(!isset($json['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Response structure invalide', 'raw' => substr($response['data'] ?? '', 0, 300)];
    }

    $content = trim($json['choices'][0]['message']['content']);

    // Nettoyage des backticks
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/i', '', $content);
    $content = trim($content);

    // Extraction JSON si nécessaire
    if($require_json && !str_starts_with($content, '{') && !str_starts_with($content, '[')) {
        if(preg_match('/\{.*\}/s', $content, $m)) {
            $content = $m[0];
        }
    }

    $parsed = $content;
    if($require_json) {
        $parsed = @json_decode($content, true);
        if(!is_array($parsed)) {
            // Tentative de réparation JSON
            $fixed = preg_replace('/,\s*([\}\]])/', '$1', $content);
            $parsed = @json_decode($fixed, true);
            if(!is_array($parsed)) {
                return ['success' => false, 'error' => 'JSON parse error', 'content' => substr($content, 0, 400)];
            }
        }
    }

    return [
        'success'         => true,
        'data'            => $parsed,
        'raw'             => $content,
        'model_used'      => $model,
        'tokens_used'     => $json['usage']['total_tokens'] ?? 0,
        'response_time_ms'=> $response['response_time_ms'] ?? 0,
    ];
}

// ============================================================================
// DATABASE FUNCTIONS
// ============================================================================
function get_db() {
    static $pdo = null;
    if($pdo === null) {
        $pdo = init_database();
    }
    return $pdo;
}

function init_database() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Table discoveries (hypothèses validées)
        $pdo->exec("CREATE TABLE IF NOT EXISTS discoveries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            hypothesis TEXT NOT NULL,
            vulgarized TEXT,
            novelty_score REAL DEFAULT 0.5,
            confidence REAL DEFAULT 0.5,
            discovery_score REAL DEFAULT 0.0,
            mechanism TEXT,
            evidence_strength TEXT DEFAULT 'moderate',
            keywords TEXT DEFAULT '[]',
            domain TEXT,
            session_id TEXT,
            loops_completed INTEGER DEFAULT 0,
            sources_used TEXT DEFAULT '[]',
            queries_made INTEGER DEFAULT 0,
            status TEXT DEFAULT 'pending_validation',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            validated_at DATETIME
        )");
        
        // Table research_logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS research_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            loop_num INTEGER DEFAULT 0,
            step INTEGER DEFAULT 0,
            phase TEXT,
            message TEXT,
            details TEXT,
            log_type TEXT DEFAULT 'info',
            data_json TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table findings (resultats bruts des APIs)
        $pdo->exec("CREATE TABLE IF NOT EXISTS findings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            loop_num INTEGER DEFAULT 0,
            source TEXT NOT NULL,
            query TEXT,
            title TEXT,
            abstract TEXT,
            url TEXT,
            relevance_score REAL DEFAULT 0.5,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table query_strategies (apprentissage)
        $pdo->exec("CREATE TABLE IF NOT EXISTS query_strategies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source TEXT NOT NULL,
            topic_type TEXT,
            optimized_term_template TEXT,
            effectiveness_score REAL DEFAULT 0.5,
            times_used INTEGER DEFAULT 0,
            last_optimized DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table source_results (résultats des sources pour les découvertes)
        $pdo->exec("CREATE TABLE IF NOT EXISTS source_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            discovery_id INTEGER NOT NULL,
            session_id TEXT,
            source TEXT NOT NULL,
            query TEXT,
            hits INTEGER DEFAULT 0,
            abstracts_text TEXT,
            title TEXT,
            abstract TEXT,
            url TEXT,
            relevance_score REAL DEFAULT 0.5,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (discovery_id) REFERENCES discoveries(id)
        )");
        
        // Index pour performance
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_logs_session ON research_logs(session_id, created_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_findings_session ON findings(session_id, loop_num)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_discoveries_score ON discoveries(discovery_score DESC)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_source_results_discovery ON source_results(discovery_id)");
        
        return $pdo;
    } catch(Exception $e) {
        error_log("DB Init Error: " . $e->getMessage());
        return null;
    }
}

function add_to_log($session_id, $loop_num, $step, $phase, $message, $details = null, $log_type = 'info', $data_json = null) {
    $pdo = get_db();
    if(!$pdo) return false;

    $stmt = $pdo->prepare("INSERT INTO research_logs (session_id, loop_num, step, phase, message, details, log_type, data_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$session_id, $loop_num, $step, $phase, $message, $details, $log_type, $data_json ? json_encode($data_json) : null]);
}

// Alias pour compatibilité avec engine.php qui utilise add_log()
function add_log($session_id, $iteration, $step, $phase, $message, $details = null, $log_type = 'info') {
    return add_to_log($session_id, $iteration, $step, $phase, $message, $details, $log_type);
}

// Fonction pour récupérer les logs d'une session
function get_logs($session_id, $limit = 50) {
    $pdo = get_db();
    if(!$pdo) return [];
    
    $stmt = $pdo->prepare("SELECT * FROM research_logs WHERE session_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$session_id, (int)$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function save_finding($session_id, $loop_num, $source, $query, $title, $abstract = '', $url = '', $relevance = 0.5) {
    $pdo = get_db();
    if(!$pdo) return false;
    
    $stmt = $pdo->prepare("INSERT INTO findings (session_id, loop_num, source, query, title, abstract, url, relevance_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$session_id, $loop_num, $source, $query, $title, $abstract, $url, $relevance]);
}

// ============================================================================
// SESSION MANAGEMENT
// ============================================================================
if(session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if(!isset($_SESSION['discovery_session'])) {
    $_SESSION['discovery_session'] = bin2hex(random_bytes(16));
}
$SESSION_ID = $_SESSION['discovery_session'];

// Initialisation database
$db = get_db();

// ============================================================================
// PROMPT LIBRARY — Optimisés pour vraies découvertes
// ============================================================================
$PROMPT_LIBRARY = [
    'target_selection' => "Tu es un expert mondial en sélection de cibles de recherche SOUS-ÉTUDIÉES avec potentiel de découverte révolutionnaire.

MISSION CRITIQUE:
1. Analyser les cibles DÉJÀ explorées (fournies en input) et les ÉVITER ABSOLUMENT
2. Identifier une cible TRÈS PEU ÉTUDIÉE (< 50 publications) mais avec signaux prometteurs
3. Prioriser les connexions INTER-DOMAINES inédites (ex: neuro-immunologie, cardio-métabolique)
4. Chercher dans les angles MORTS de la science contemporaine

FORMAT JSON STRICT:
{
  \"next_target\": \"<cible precise: maladie rare, gène peu étudié, mécanisme spécifique>\",
  \"domain\": \"<domaine principal>\",
  \"secondary_domain\": \"<deuxième domaine pour connexion interdisciplinaire>\",
  \"reasoning\": \"<pourquoi cette cible est prometteuse ET sous-étudiée>\",
  \"novelty_score\": <0.0-1.0>,
  \"ignored_sources_hint\": [\"<source 1 souvent ignorée>\", \"<source 2>\"],
  \"suggested_queries\": [\"<requête 1 optimisée>\", \"<requête 2>\", \"<requête 3>\", \"<requête 4>\", \"<requête 5>\"],
  \"potential_impact\": \"<impact therapeutique ou scientifique>\",
  \"risk_factors\": [\"<risque 1>\", \"<risque 2>\"]
}

CONTRAINTES:
- Jamais de cibles génériques ('cancer', 'diabetes') — toujours sous-types spécifiques
- Priorité maladies rares (< 200 000 patients) avec mécanismes mal compris
- Favoriser connexions inattendues entre domaines disjoints",

    'query_optimization' => "Tu es un expert en optimisation de requêtes pour APIs scientifiques.

OBJECTIF: Maximiser le nombre de resultats PERTINENTS retournés par chaque API.

STRATÉGIE:
1. Analyser la source cible et son format de requête optimal
2. Générer MULTIPLE variantes de la requête (5-10 versions)
3. Inclure synonymes, termes techniques, acronymes, noms alternatifs
4. Adapter la syntaxe à chaque API spécifique

INPUT:
- Source: {SOURCE}
- Sujet: {TOPIC}
- Resultats précédents: {PREVIOUS_HITS} hits

FORMAT JSON:
{
  \"optimized_queries\": [
    {\"query\": \"<version 1>\", \"rationale\": \"<pourquoi cette formulation>\"},
    {\"query\": \"<version 2>\", \"rationale\": \"<pourquoi>\"},
    {\"query\": \"<version 3>\", \"rationale\": \"<pourquoi>\"},
    {\"query\": \"<version 4>\", \"rationale\": \"<pourquoi>\"},
    {\"query\": \"<version 5>\", \"rationale\": \"<pourquoi>\"}
  ],
  \"recommended_source_order\": [\"<source 1>\", \"<source 2>\", \"<source 3>\"],
  \"expected_total_hits\": <estimation du nombre total de resultats>
}",

    'hypothesis_generation' => "Tu es un chercheur de renommée mondiale spécialisé en génération d'hypothèses RÉVOLUTIONNAIRES.

DONNÉES: Tu as accès aux resultats de {LOOP_NUM} boucles de recherche, {TOTAL_QUERIES} requêtes APIs, et {SOURCES_COUNT} sources différentes.

MISSION: Générer une hypothèse QUI SOIT:
1. SPÉCIFIQUE: Affirmation testable précisément définie
2. INÉDITE: Mécanisme ou connexion NON décrit dans littérature actuelle
3. TESTABLE: Protocole experimental clair peut être dérivé
4. IMPACTANTE: Potentiel de changer le paradigme actuel
5. SOLIDEMENT SUPPORTÉE: Par les données collectées

PROCESSUS:
a) Croiser les données de PLUSIEURS sources indépendantes
b) Identifier patterns/corrélations invisibles source-par-source
c) Formuler mécanisme causal plausible
d) Vérifier que ce mécanisme n'est pas déjà établi
e) Dériver prédiction testable

FORMAT JSON STRICT:
{
  \"hypothesis\": \"<hypothèse technique precise en 1-2 phrases>\",
  \"vulgarized\": \"<explication accessible, 2-3 phrases, analogie concrète>\",
  \"novelty_score\": <0.0-1.0, 1.0=totalement inédit>,
  \"confidence\": <0.0-1.0, basé sur solidité des données>,
  \"discovery_score\": <0.0-1.0, combinaison nouveauté+confiance+impact>,
  \"mechanism\": \"<description détaillée du mécanisme moléculaire/cellulaire>\",
  \"actionable\": \"<protocole experimental concret>\",
  \"therapeutic_target\": \"<cible therapeutique precise si applicable>\",
  \"evidence_strength\": \"<weak|moderate|strong|very_strong>\",
  \"research_gaps\": \"<lacunes critiques comblees>\",
  \"keywords\": [\"<mot-cle 1>\", \"<mot-cle 2>\", \"<mot-cle 3>\", \"<mot-cle 4>\", \"<mot-cle 5>\"],
  \"predicted_outcomes\": [\"<resultat attendu 1>\", \"<resultat attendu 2>\"],
  \"alternative_explanations\": [\"<alternative 1>\", \"<alternative 2>\"],
  \"needs_more_loops\": <true|false>,
  \"revision_notes\": \"<si needs_more_loops=true, ce qu'il faut explorer de plus>\"
}
CRITERES:
- discovery_score > 0.75 requis pour validation finale
- Si < 0.75, preciser ce qui manque (needs_more_loops=true)",

    'critique_contradiction' => "Tu es un critique scientifique IMPITOYABLE, expert en identification de failles.

MISSION: Analyser l'hypothèse avec ESPRIT CRITIQUE MAXIMAL.

APPROCHE:
- Assume que l'hypothèse est FAUSSE jusqu'à preuve du contraire
- Cherche activement contre-exemples et incohérences
- Questionne chaque affirmation: \"Quelle preuve DIRECTE supporte ceci?\"
- Identifie corrélations spurious présentées comme causales

FORMAT JSON:
{
  \"overall_assessment\": \"<prometteuse|douteuse|fausse>\",
  \"validity_score\": <0.0-1.0>,
  \"logical_flaws\": [
    {\"flaw\": \"<description>\", \"severity\": \"<minor|major|critical>\", \"fix\": \"<correction>\"}
  ],
  \"missing_evidence\": [\"<donnée manquante 1>\", \"<donnée manquante 2>\"],
  \"alternative_explanations\": [
    {\"explanation\": \"<alternative>\", \"likelihood\": \"<low|medium|high>\"}
  ],
  \"overinterpretations\": [\"<affirmation exagérée 1>\"],
  \"confirmation_bias_risk\": \"<low|medium|high>\",
  \"recommended_additional_sources\": [\"<source 1>\", \"<source 2>\"],
  \"verdict\": \"<reject|revise_and_reloop|accept_with_caution|accept>\",
  \"required_revisions\": [\"<révision 1>\", \"<révision 2>\"],
  \"should_continue_loops\": <true|false>,
  \"focus_for_next_loops\": [\"<angle 1 à explorer>\", \"<angle 2>\"]
}

ATTITUDE: Dur mais juste. Chaque critique argumentée. Voies d'amélioration proposées.",

    'source_diversification' => "Tu es un expert en stratégie de recherche documentaire scientifique.

OBJECTIF: Identifier les sources SOUS-UTILISÉES ou IGNORÉES qui pourraient contenir des données cruciales.

ANALYSE:
- Sources déjà consultées: {USED_SOURCES}
- Domaine de recherche: {DOMAIN}
- Cible: {TARGET}

MISSION: Recommander 5-10 sources ADDITIONNELLES à explorer, en priorisant:
1. Sources spécialisées peu connues
2. Préprints récents (derniers 6 mois)
3. Bases de données de pays non-occidentaux
4. Brevets et littérature grise
5. Données brutes non publiées

FORMAT JSON:
{
  \"recommended_sources\": [
    {\"source\": \"<nom>\", \"reason\": \"<pourquoi pertinent>\", \"expected_yield\": \"<high|medium|low>\"},
    ...
  ],
  \"ignored_angles\": [\"<angle de recherche négligé 1>\", \"<angle 2>\"],
  \"cross_domain_opportunities\": [\"<connexion domaine A-B>\", \"<connexion C-D>\"],
  \"temporal_focus\": \"<période temporelle à privilégier>\"
}",
];