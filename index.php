<?php
	require_once 'persistance/dialogueBD.php';

	$tabAppConso = [];
	$tabEvoMensu = [];
	$tabComparaison = [];
	$erreur = null;

	try {
		$undlg = new DialogueBD();
		$tabAppConso    = $undlg->getConsommationApplications();
		$tabEvoMensu    = $undlg->getEvolutionMensuelle();
		$tabComparaison = $undlg->getComparaison();
	} catch (Exception $e) {
		$erreur = $e->getMessage();
	}

	// ── Stats Tab 1
	$top5       = array_slice($tabAppConso, 0, 5);
	$totalTop5  = array_sum(array_column($top5, 'volume'));
	$top1       = $top5[0] ?? ['nom' => 'N/A', 'volume' => 0];

	// ── Stats Tab 2
	$volumes    = array_column($tabEvoMensu, 'volume');
	$minVol     = !empty($volumes) ? (int) min($volumes) : 0;
	$maxVol     = !empty($volumes) ? (int) max($volumes) : 0;
	$minIdx     = !empty($volumes) ? array_search(min($volumes), $volumes) : 0;
	$maxIdx     = !empty($volumes) ? array_search(max($volumes), $volumes) : 0;
	$minMois    = $tabEvoMensu[$minIdx]['mois'] ?? '';
	$maxMois    = $tabEvoMensu[$maxIdx]['mois'] ?? '';

	// ── Globals
	$totalStockage = array_sum(array_column($tabComparaison, 'stockage'));
	$totalReseau   = array_sum(array_column($tabComparaison, 'reseau'));
	$totalGlobal   = $totalStockage + $totalReseau;
	$pctStockage   = $totalGlobal > 0 ? round(($totalStockage / $totalGlobal) * 100, 1) : 50;
	$pctReseau     = $totalGlobal > 0 ? round(($totalReseau / $totalGlobal) * 100, 1) : 50;
?>

<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Campus IT — DCS Games</title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="style.css">
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/highcharts-3d.js"></script>
	</head>
	<body>

	<!-- ══════════════════════ HEADER ══════════════════════ -->
	<header>
		<div class="header-left" style="padding-left: 8px;">
			<span class="header-title">Campus IT - DCS Games FOXTROT</span>
		</div>
		<div class="header-right">
			<button class="icon-btn" id="btnThemeToggle" title="Thème (Clair/Sombre/Contraste)">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
			</button>
			<button class="icon-btn" id="btn3dToggle" title="Mode 3D (ON/OFF)">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
			</button>
		</div>
	</header>

	<!-- ══════════════════════ TAB NAV ══════════════════════ -->
	<div class="tab-nav">
		<button class="tab-btn active" onclick="switchTab(event,'tab0')">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
			Vue Globale
		</button>
		<button class="tab-btn" onclick="switchTab(event,'tab1')">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
			Top Applications
		</button>
		<button class="tab-btn" onclick="switchTab(event,'tab2')">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
			Évolution Mensuelle
		</button>
		<button class="tab-btn" onclick="switchTab(event,'tab3')">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
			Comparaison
		</button>
	</div>

	<!-- ══════════════════════ MAIN ══════════════════════ -->
	<main>
		<?php if ($erreur): ?>
		<div class="error-banner">⚠️ <?= htmlspecialchars($erreur) ?></div>
		<?php endif; ?>

		<!-- ════ TAB 0 : VUE GLOBALE ════ -->
		<div id="tab0" class="tabcontent active">
			<!-- Top KPI Row -->
			<div class="kpi-grid">
				<!-- Volume Total -->
				<div class="card stat-card" data-glow="purple">
					<p class="stat-label">Volume Total (Global)</p>
					<div class="stat-big counter" data-target="<?= $totalGlobal ?>">0</div>
					<span class="badge badge-purple">Données cumulées</span>
					<div class="glow-orb purple"></div>
				</div>
				
				<!-- Stockage vs Réseau -->
				<div class="card stat-card" data-glow="cyan">
					<p class="stat-label">Répartition Globale</p>
					<div class="repart-flex">
						<div class="repart-item">
							<span class="repart-pct cyan"><?= $pctStockage ?>%</span>
							<span class="repart-lbl">Total Stockage</span>
							<span class="repart-vol"><?= number_format($totalStockage, 0, ',', ' ') ?> Go</span>
						</div>
						<div class="repart-item text-right">
							<span class="repart-pct green"><?= $pctReseau ?>%</span>
							<span class="repart-lbl">Total Réseau</span>
							<span class="repart-vol"><?= number_format($totalReseau, 0, ',', ' ') ?> Go</span>
						</div>
					</div>
					<div class="progress-bar-dual mt">
						<div class="progress-cyan" style="width: <?= $pctStockage ?>%"></div>
						<div class="progress-green" style="width: <?= $pctReseau ?>%"></div>
					</div>
					<div class="glow-orb cyan"></div>
				</div>

				<!-- App Dominante -->
				<div class="card stat-card" data-glow="amber">
					<p class="stat-label">Application Dominante</p>
					<div class="stat-app-name"><?= htmlspecialchars($top1['nom']) ?></div>
					<div class="stat-app-vol">
						<span>👑</span>
						<span class="counter amber" data-target="<?= (int)$top1['volume'] ?>">0</span> Go
					</div>
					<div class="glow-orb amber"></div>
				</div>
			</div>
			
			<!-- Charts Row -->
			<div class="chart-grid mt">
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
							Évolution Mensuelle Globale
						</div>
					</div>
					<div class="chart-box"><canvas id="globalAreaChart"></canvas></div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#06b6d4" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
							Détail Stockage vs Réseau
						</div>
					</div>
					<div class="chart-box"><canvas id="globalBarChart"></canvas></div>
				</div>
			</div>
		</div>

		<!-- ════ TAB 1 : TOP APPLICATIONS ════ -->
		<div id="tab1" class="tabcontent">
			<div class="tab1-grid">

				<!-- Classement -->
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#a78bfa" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/></svg>
							Classement
						</div>
						<span class="pill">VOLUME 5M</span>
					</div>
					<table class="rank-table">
						<thead>
							<tr><th>RANG</th><th>APPLICATION</th><th>VOLUME CONSOMMÉ</th></tr>
						</thead>
						<tbody>
						<?php
						$rankColors = ['#7c3aed','#2563eb','#059669','#374151','#374151'];
						foreach ($top5 as $i => $app):
							$v = (int)$app['volume'];
							$pct = $totalTop5 > 0 ? round($v / $totalTop5 * 100) : 0;
						?>
							<tr class="rank-row">
								<td><span class="rank-badge" style="background:<?= $rankColors[$i] ?>">#<?= $i+1 ?></span></td>
								<td>
									<span class="app-name"><?= htmlspecialchars($app['nom']) ?></span>
									<div class="mini-bar-wrap"><div class="mini-bar" style="width:<?= $pct ?>%;background:<?= $rankColors[$i] ?>"></div></div>
								</td>
								<td class="app-vol"><?= number_format($v, 0, ',', ' ') ?> Go</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Donut -->
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#06b6d4" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
							Répartition
						</div>
					</div>
					<div class="donut-wrap">
						<div id="donutChart" style="width:100%; height:110%;"></div>
					</div>
					<div id="donutLegend" class="donut-legend"></div>
				</div>

				<!-- Stat cards -->
				<div class="stat-col">
					<div class="card stat-card" data-glow="green">
						<p class="stat-label">Consommation Totale Top 5</p>
						<div class="stat-big counter" data-target="<?= $totalTop5 ?>">0</div>
						<span class="badge badge-green">↗ Poids majeur</span>
						<div class="glow-orb green"></div>
					</div>
					<div class="card stat-card" data-glow="purple">
						<p class="stat-label">Application #1 Dominante</p>
						<div class="stat-app-name"><?= htmlspecialchars($top1['nom']) ?></div>
						<div class="stat-app-vol">
							<span>👑</span>
							<span class="counter cyan" data-target="<?= (int)$top1['volume'] ?>">0</span> Go
						</div>
						<div class="glow-orb purple"></div>
					</div>
				</div>

			</div>
		</div>

		<!-- ════ TAB 2 : ÉVOLUTION MENSUELLE ════ -->
		<div id="tab2" class="tabcontent">
			<div class="tab2-grid">
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
							Tendance Annuelle
						</div>
					</div>
					<div class="chart-box"><canvas id="areaChart"></canvas></div>
				</div>
				<div class="stat-col">
					<div class="card stat-card" data-glow="red">
						<p class="stat-label">Minimum Historique</p>
						<div class="stat-big counter" data-target="<?= $minVol ?>">0</div>
						<span class="badge badge-red">↓ <?= htmlspecialchars($minMois) ?></span>
						<div class="glow-orb red"></div>
					</div>
					<div class="card stat-card" data-glow="cyan">
						<p class="stat-label">Pic Historique</p>
						<div class="stat-big counter" data-target="<?= $maxVol ?>">0</div>
						<span class="badge badge-cyan">↑ <?= htmlspecialchars($maxMois) ?></span>
						<div class="glow-orb cyan"></div>
					</div>
				</div>
			</div>
			<div class="card mt">
				<div class="card-header">
					<div class="card-title">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
						Historique
					</div>
				</div>
				<table class="data-table">
					<thead><tr><th>MOIS</th><th>VOLUME TOTAL</th></tr></thead>
					<tbody>
					<?php foreach ($tabEvoMensu as $l): ?>
						<tr><td class="td-bold"><?= htmlspecialchars($l['mois']) ?></td><td><?= number_format((int)$l['volume'], 0, ',', ' ') ?> Go</td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ════ TAB 3 : COMPARAISON ════ -->
		<div id="tab3" class="tabcontent">
			<div class="tab3-grid">
				<div class="card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
							Stockage / Réseau
						</div>
					</div>
					<div class="chart-box"><canvas id="barChart"></canvas></div>
				</div>
				<div class="card ratio-card">
					<div class="card-header">
						<div class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#06b6d4" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							Ratio
						</div>
					</div>
					<div class="ratio-body">
						<div id="ratioVal" class="ratio-val">0</div>
						<div class="ratio-sub">TOTAL GO</div>
					</div>
					<div class="glow-orb green" style="bottom:-30px;left:50%;transform:translateX(-50%)"></div>
				</div>
			</div>
			<div class="card mt">
				<div class="card-header">
					<div class="card-title">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.657 4.03 3 9 3s9-1.343 9-3V5"/><path d="M3 12c0 1.657 4.03 3 9 3s9-1.343 9-3"/></svg>
						Data Détaillée
					</div>
				</div>
				<table class="data-table">
					<thead><tr><th>MOIS</th><th>STOCKAGE</th><th>RÉSEAU</th><th>DELTA</th></tr></thead>
					<tbody>
					<?php foreach ($tabComparaison as $l):
						$delta = (int)$l['stockage'] - (int)$l['reseau'];
						$cls   = $delta >= 0 ? 'delta-pos' : 'delta-neg';
						$str   = ($delta >= 0 ? '+' : '') . $delta;
					?>
						<tr>
							<td class="td-bold"><?= htmlspecialchars($l['mois']) ?></td>
							<td><?= number_format((int)$l['stockage'], 0, ',', ' ') ?> Go</td>
							<td><?= number_format((int)$l['reseau'],   0, ',', ' ') ?> Go</td>
							<td><span class="delta <?= $cls ?>"><?= $str ?></span></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</main>

	<script>
		const appConso    = <?= json_encode($tabAppConso) ?>;
		const evoMensu    = <?= json_encode($tabEvoMensu) ?>;
		const comparaison = <?= json_encode($tabComparaison) ?>;
	</script>
	
	<script src="script.js"></script>
	
	</body>
</html>