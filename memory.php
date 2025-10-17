<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/autoload.php';

use App\Game;
use App\Card;
use App\Player;
// use App\Storage\JsonStore;
// $store = new JsonStore(__DIR__ . '/data');
$game = new Game();

// Simple router via action parameter
$action = $_GET['action'] ?? 'home';

// Ensure session state shape
$_SESSION['memory'] = $_SESSION['memory'] ?? [
	'player' => null,
	'pairs' => null,
	'deck' => [], // array of Card serialized as arrays
	'revealed' => [], // indexes currently revealed in the attempt
	'matched' => [], // indexes permanently matched
	'moves' => 0,
	'start' => null,
];

function serializeDeck(array $deck): array {
	$out = [];
	foreach ($deck as $c) {
		$out[] = ['id' => $c->id, 'label' => $c->label];
	}
	return $out;
}

function hydrateDeck(array $raw): array {
	$out = [];
	foreach ($raw as $r) {
		$out[] = new Card((int)$r['id'], (string)$r['label']);
	}
	return $out;
}

if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string)($_POST['name'] ?? ''));
	$pairs = (int)($_POST['pairs'] ?? Game::MIN_PAIRS);
	if ($name === '') $name = 'Guest';
	try {
		$deck = $game->createDeck($pairs);
		$_SESSION['memory'] = [
			'player' => $name,
			'pairs' => $pairs,
			'deck' => serializeDeck($deck),
			'revealed' => [],
			'matched' => [],
			'moves' => 0,
			'start' => time(),
		];
		header('Location: ?action=play');
		exit;
	} catch (Throwable $e) {
		$error = $e->getMessage();
		renderHome($error ?? null);
		exit;
	}
}

if ($action === 'flip' && isset($_GET['i'])) {
	$i = (int)$_GET['i'];
	$state = &$_SESSION['memory'];
	$deck = hydrateDeck($state['deck']);
	if (!isset($deck[$i]) || in_array($i, $state['matched'], true) || in_array($i, $state['revealed'], true)) {
		header('Location: ?action=play');
		exit;
	}
	$state['revealed'][] = $i;
	if (count($state['revealed']) === 2) {
		$state['moves']++;
		[$a, $b] = $state['revealed'];
		if ($deck[$a]->id === $deck[$b]->id) {
			$state['matched'][] = $a;
			$state['matched'][] = $b;
		}
	} elseif (count($state['revealed']) > 2) {
		// Reset to keep only the last flipped card when a new attempt starts
		$last = array_pop($state['revealed']);
		$state['revealed'] = [$last];
	}
	header('Location: ?action=play');
	exit;
}

if ($action === 'next') {
	// Called after user acknowledges mismatch; clear revealed
	$_SESSION['memory']['revealed'] = [];
	header('Location: ?action=play');
	exit;
}

if ($action === 'restart') {
	$_SESSION['memory'] = [
		'player' => null,
		'pairs' => null,
		'deck' => [],
		'revealed' => [],
		'matched' => [],
		'moves' => 0,
		'start' => null,
	];
	header('Location: ?action=home');
	exit;
}

if ($action === 'profile') {
	$name = (string)($_GET['name'] ?? '');
	$player = $name ? $store->loadPlayer($name) : null;
	renderProfile($player);
	exit;
}

if ($action === 'play') {
	$state = $_SESSION['memory'];
	if (!$state['deck']) {
		renderHome();
		exit;
	}
	$deck = hydrateDeck($state['deck']);
	$done = count($state['matched']) >= count($deck);
	if ($done) {
		$seconds = max(0, time() - (int)$state['start']);
		$moves = (int)$state['moves'];
		$pairs = (int)$state['pairs'];
		// Persist in leaderboard for this number of pairs
		$lb = $store->loadLeaderboard($pairs);
		$lb->add((string)$state['player'], $moves, $seconds, $pairs);
		$store->saveLeaderboard($lb, $pairs);
		// Stockage JSON supprimé, tout passe par MySQL
		renderWin($state, $moves, $seconds);
		exit;
	}
	renderBoard($deck, $state);
	exit;
}

// Default: home and leaderboard
renderHome();
exit;

// ------------------------------ Views ------------------------------

function renderHome(?string $error = null): void {
		global $store;
		// Détermine la difficulté sélectionnée pour le classement (GET ou défaut 6)
		$selectedPairs = isset($_GET['classement_pairs']) ? (int)$_GET['classement_pairs'] : 6;
		if ($selectedPairs < Game::MIN_PAIRS || $selectedPairs > Game::MAX_PAIRS) $selectedPairs = 6;
		$lb = new App\Leaderboard();
		$top = $lb->top($selectedPairs);
	       echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Memory Game</title>';
	       echo '<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">';
	       echo '<link rel="stylesheet" href="style.css">';
	       echo '</head><body><main>';
	echo '<h1>Memory Game</h1>';
	if ($error) echo '<p style="color:#b00">' . htmlspecialchars($error) . '</p>';
	echo '<h2>Nouvelle partie</h2>';
	echo '<form method="post" action="?action=start">';
	echo '<label>Nom du joueur <input name="name" required></label> ';
	echo '<label>Paires <input type="number" name="pairs" min="' . Game::MIN_PAIRS . '" max="' . Game::MAX_PAIRS . '" value="6"></label> ';
	echo '<button class="btn" type="submit">Démarrer</button>';
	echo '</form>';

	echo '<h2>Top 10</h2>';
	echo '<form method="get" class="difficulty-form">
		<label for="classement_pairs" class="difficulty-label">Difficulté : </label>
		<select name="classement_pairs" id="classement_pairs" class="difficulty-select" onchange="this.form.submit()">';
	for ($p = Game::MIN_PAIRS; $p <= Game::MAX_PAIRS; $p++) {
		$sel = ($p === $selectedPairs) ? ' selected' : '';
		echo '<option value="' . $p . '"' . $sel . '>' . $p . ' paires</option>';
	}
	echo '</select></form>';

	if (!$top) {
		echo '<p>Aucun score encore pour cette difficulté.</p>';
	} else {
		echo '<table><thead><tr><th>#</th><th>Joueur</th><th>Coups</th><th>Temps</th><th>Paires</th></tr></thead><tbody>';
		foreach ($top as $i => $e) {
			echo '<tr><td>' . ($i+1) . '</td><td><a class="player-name" href="?action=profile&name=' . urlencode($e['name']) . '">' . htmlspecialchars($e['name']) . '</a></td><td>' . (int)$e['moves'] . '</td><td>' . (int)$e['seconds'] . 's</td><td>' . (int)$e['pairs'] . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</main></body></html>';
}

function renderBoard(array $deck, array $state): void {
	echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Memory - Partie</title><meta http-equiv="refresh" content="">';
       echo '<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">';
       echo '<link rel="stylesheet" href="style.css">';
       echo '</head><body><main>';
	echo '<div class="controls">';
	echo '<a class="btn" href="?action=restart">Revenir à l\'accueil</a> ';
	echo '<span>Joueur: ' . htmlspecialchars((string)$state['player']) . '</span> | ';
	echo '<span>Paires: ' . (int)$state['pairs'] . '</span> | ';
	echo '<span>Coups: ' . (int)$state['moves'] . '</span>';
	echo '</div>';

	// Floating attempts counter
	echo '<div class="counter" aria-live="polite">Coups: ' . (int)$state['moves'] . '</div>';

	$revealed = $state['revealed'];
	$matched = array_flip($state['matched']);
	echo '<div class="board">';
	foreach ($deck as $i => $card) {
		$isRevealed = in_array($i, $revealed, true);
		$isMatched = isset($matched[$i]);
		$class = 'card' . ($isRevealed ? ' revealed' : '') . ($isMatched ? ' matched' : '');
		if ($isRevealed || $isMatched) {
			echo '<div class="' . $class . '">' . htmlspecialchars($card->label) . '</div>';
		} else {
			echo '<a class="' . $class . '" href="?action=flip&i=' . $i . '" aria-label="Flip card">?</a>';
		}
	}
	echo '</div>';

	if (count($revealed) === 2) {
		echo '<p><a class="btn" href="?action=next">Continuer</a></p>';
	}
	echo '</main></body></html>';
}

function renderWin(array $state, int $moves, int $seconds): void {
	echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Memory - Gagné</title>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">';
	echo '<link rel="stylesheet" href="style.css">';
	echo '</head><body><main>';
	echo '<h1>Bravo ' . htmlspecialchars((string)$state['player']) . ' 🎉</h1>';
	echo '<p>Coups: <strong>' . $moves . '</strong> | Paires: ' . (int)$state['pairs'] . ' | Temps: ' . $seconds . 's</p>';
	echo '<p><a class="btn" href="?action=restart">Nouvelle partie</a></p>';
	echo '</main></body></html>';
}

function renderProfile(?App\Player $player): void {
	echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Profil</title>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">';
	echo '<link rel="stylesheet" href="style.css">';
	echo '</head><body><main>';
	if (!$player) {
		echo '<p>Profil introuvable.</p><p><a href="?">Retour</a></p>';
		echo '</main></body></html>';
		return;
	}
	echo '<h1>Profil de ' . htmlspecialchars($player->name) . '</h1>';
	$scores = $player->getScores();
	if (!$scores) {
		echo '<p>Aucun score pour le moment.</p>';
	} else {
		echo '<table><thead><tr><th>Date</th><th>Paires</th><th>Coups</th><th>Temps</th></tr></thead><tbody>';
		foreach ($scores as $s) {
			echo '<tr><td>' . htmlspecialchars((string)$s['date']) . '</td><td>' . (int)$s['pairs'] . '</td><td>' . (int)$s['moves'] . '</td><td>' . (int)$s['seconds'] . 's</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '<p><a href="?">Retour à l\'accueil</a></p>';
	echo '</body></html>';
}

