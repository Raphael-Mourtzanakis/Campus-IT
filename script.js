/* ═══════════════════════════════════════════════
   CAMPUS IT — DCS Games  |  dashboard script
═══════════════════════════════════════════════ */

Chart.defaults.color = '#5c6a82';
Chart.defaults.borderColor = '#1a2236';
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

const PALETTE = ['#818cf8', '#06b6d4', '#22c55e', '#f472b6', '#f59e0b'];

let _is3D = false;
let currentTheme = 'dark';
const THEMES = ['dark', 'light', 'contrast'];

/* ─── COUNTERS ─── */
function animCounter(el) {
  const target = parseInt(el.dataset.target) || 0;
  const duration = 1400;
  const start = performance.now();
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const e = 1 - Math.pow(1 - p, 4);
    el.textContent = Math.round(e * target).toLocaleString('fr-FR');
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}
function runCounters() {
  document.querySelectorAll('.tabcontent.active .counter').forEach(animCounter);
}

/* ─── TABS ─── */
function switchTab(evt, id) {
  document.querySelectorAll('.tabcontent').forEach(t => {
    t.classList.remove('active');
    t.style.display = 'none';
  });
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

  const tab = document.getElementById(id);
  tab.style.display = 'block';
  void tab.offsetWidth;
  tab.classList.add('active');
  evt.currentTarget.classList.add('active');

  if (id === 'tab1') {
    initDonut();
    if (_donut) setTimeout(() => _donut.reflow(), 100);
  }
  if (id === 'tab0' || id === 'tab2') initArea();
  if (id === 'tab0' || id === 'tab3') initBar();
  applyTheme(currentTheme);
  runCounters();
}

/* ─── DONUT (Tab 1) — Highcharts ─── */
let _donut = null;
let _centerLabel = null;

function updateCenterLabel() {
  if (!_donut || !_donut.series || !_donut.series[0]) return;
  const series = _donut.series[0];
  const cx = series.center[0] + _donut.plotLeft;
  const cy = series.center[1] + _donut.plotTop;
  const total = appConso.slice(0, 5).reduce((s, a) => s + parseInt(a.volume), 0);
  const txt = total.toLocaleString('fr-FR');
  const fontSize = _is3D ? 20 : 26;

  if (_centerLabel) {
    _centerLabel.destroy();
  }
  _centerLabel = _donut.renderer.text(
    txt, 0, 0
  )
    .css({
      fontSize: fontSize + 'px',
      fontWeight: '800',
      fontFamily: "'Inter', system-ui, sans-serif",
      color: currentTheme === 'light' ? '#0f172a' : '#f0f4ff',
      textShadow: '0 2px 12px rgba(0,0,0,0.6)'
    })
    .attr({ zIndex: 10 })
    .add();

  const bbox = _centerLabel.getBBox();
  _centerLabel.attr({
    x: cx - bbox.width / 2,
    y: cy + bbox.height / 4
  });
}

function initDonut() {
  if (_donut) return;
  const top5 = appConso.slice(0, 5);
  const data = top5.map((a, i) => ({
    name: a.nom,
    y: parseInt(a.volume),
    color: PALETTE[i % PALETTE.length]
  }));

  const leg = document.getElementById('donutLegend');
  if (leg) {
    leg.innerHTML = '';
    top5.forEach((a, i) => {
      const div = document.createElement('div');
      div.className = 'legend-item';
      div.innerHTML = `<span class="legend-dot" style="background:${PALETTE[i % PALETTE.length]}"></span>${a.nom}`;
      leg.appendChild(div);
    });
  }

  _donut = Highcharts.chart('donutChart', {
    chart: {
      type: 'pie',
      backgroundColor: 'transparent',
      options3d: { enabled: _is3D, alpha: 50, beta: 0 },
      events: {
        render: function () { updateCenterLabel(); }
      }
    },
    title: { text: null },
    credits: { enabled: false },
    tooltip: {
      pointFormat: '<b>{point.y:,.0f} Go</b> ({point.percentage:.1f}%)',
      backgroundColor: 'rgba(15, 22, 35, 0.92)',
      style: { color: '#f0f4ff', fontSize: '13px' },
      borderWidth: 1,
      borderColor: '#2a3550',
      borderRadius: 10,
      shadow: true
    },
    plotOptions: {
      pie: {
        innerSize: '70%',
        depth: 45,
        dataLabels: { enabled: false },
        borderWidth: 2,
        borderColor: '#0f1623',
        showInLegend: false,
        cursor: 'pointer',
        states: { hover: { brightness: 0.12, halo: { size: 8 } } }
      }
    },
    series: [{
      name: 'Volume',
      data: data,
      animation: { duration: 1000, easing: 'easeOutQuad' }
    }]
  });
}

function toggle3DMode() {
  _is3D = !_is3D;
  const btn3d = document.getElementById('btn3dToggle');
  if (btn3d) btn3d.style.color = _is3D ? 'var(--cyan)' : 'var(--muted)';

  if (_donut) {
    _donut.update({
      chart: { options3d: { alpha: _is3D ? 50 : 0, enabled: _is3D } },
      plotOptions: {
        pie: {
          innerSize: _is3D ? '50%' : '70%',
          depth: _is3D ? 45 : 0,
          borderWidth: _is3D ? 0 : 2
        }
      }
    });
  }
}

/* ─── AREA CHART (Tab 0 & 2) ─── */
let _area = null, _globalArea = null;
function initArea() {
  const labels = evoMensu.map(d => d.mois);
  const data = evoMensu.map(d => parseInt(d.volume));

  const createLineChart = (ctxId) => {
    const ctxEl = document.getElementById(ctxId);
    if (!ctxEl) return null;
    const ctx = ctxEl.getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(129,140,248,.45)');
    grad.addColorStop(.6, 'rgba(129,140,248,.12)');
    grad.addColorStop(1, 'rgba(129,140,248,.01)');

    return new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data,
          borderColor: '#818cf8',
          backgroundColor: grad,
          borderWidth: 2.5,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#818cf8',
          pointBorderColor: '#080b12',
          pointBorderWidth: 2,
          tension: .4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 1000, easing: 'easeInOutQuart' },
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(26,34,54,.7)' }, ticks: { font: { size: 10 }, maxRotation: 45 } },
          y: { grid: { color: 'rgba(26,34,54,.7)' }, ticks: { font: { size: 11 } } }
        }
      }
    });
  };

  if (!_area) _area = createLineChart('areaChart');
  if (!_globalArea) _globalArea = createLineChart('globalAreaChart');
}

/* ─── BAR CHART (Tab 0 & 3) ─── */
let _bar = null, _globalBar = null;
function initBar() {
  const labels = comparaison.map(d => d.mois);
  const stockage = comparaison.map(d => parseInt(d.stockage));
  const reseau = comparaison.map(d => parseInt(d.reseau));

  const createBarChart = (ctxId) => {
    const ctxEl = document.getElementById(ctxId);
    if (!ctxEl) return null;
    const ctx = ctxEl.getContext('2d');
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Stockage',
            data: stockage,
            backgroundColor: 'rgba(129,140,248,.75)',
            borderRadius: 6,
            borderSkipped: false
          },
          {
            label: 'Réseau',
            data: reseau,
            backgroundColor: 'rgba(6,182,212,.75)',
            borderRadius: 6,
            borderSkipped: false
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: {
          legend: {
            display: true,
            labels: { color: '#8898b8', font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 }
          }
        },
        scales: {
          x: { grid: { color: 'rgba(26,34,54,.6)' }, ticks: { font: { size: 10 }, maxRotation: 45 } },
          y: { grid: { color: 'rgba(26,34,54,.6)' }, ticks: { font: { size: 11 } } }
        }
      }
    });
  };

  // Ratio card
  if (!_bar) {
    const totalS = stockage.reduce((a, b) => a + b, 0);
    const totalR = reseau.reduce((a, b) => a + b, 0);
    const ratio = totalS - totalR;
    const ratioEl = document.getElementById('ratioVal');
    if (ratioEl) {
      ratioEl.dataset.target = ratio;
      animCounter(ratioEl);
    }
  }

  if (!_bar) _bar = createBarChart('barChart');
  if (!_globalBar) _globalBar = createBarChart('globalBarChart');
}

/* ─── THEME MANAGER ─── */
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  const color = theme === 'light' ? '#0f172a' : (theme === 'contrast' ? '#ffffff' : '#5c6a82');
  const borderColor = theme === 'light' ? '#e2e8f0' : (theme === 'contrast' ? '#ffff00' : '#1a2236');
  const gridColor = theme === 'light' ? 'rgba(0,0,0,0.05)' : (theme === 'contrast' ? 'rgba(255,255,255,0.2)' : 'rgba(26,34,54,.7)');

  Chart.defaults.color = color;
  Chart.defaults.borderColor = borderColor;

  // Update all Chart.js instances
  [_area, _globalArea, _bar, _globalBar].forEach(chart => {
    if (chart) {
      if (chart.options.scales?.x) chart.options.scales.x.grid.color = gridColor;
      if (chart.options.scales?.y) chart.options.scales.y.grid.color = gridColor;
      chart.update();
    }
  });

  // Update Highcharts donut
  if (_donut && typeof Highcharts !== 'undefined') {
    const hcBg = theme === 'light' ? 'rgba(255,255,255,0.92)' : (theme === 'contrast' ? '#000' : 'rgba(15,22,35,0.92)');
    const hcText = theme === 'light' ? '#0f172a' : (theme === 'contrast' ? '#fff' : '#f0f4ff');
    const pieBorder = theme === 'light' ? '#ffffff' : (theme === 'contrast' ? '#000000' : '#0f1623');
    _donut.update({
      title: { style: { color: hcText } },
      tooltip: { backgroundColor: hcBg, style: { color: hcText }, borderColor: borderColor },
      plotOptions: { pie: { borderColor: pieBorder } }
    });
  }
}

function initTheme() {
  const match = document.cookie.match(/theme=([^;]+)/);
  if (match && THEMES.includes(match[1])) {
    currentTheme = match[1];
  }
  applyTheme(currentTheme);

  const btn = document.getElementById('btnThemeToggle');
  if (btn) {
    btn.addEventListener('click', () => {
      let idx = THEMES.indexOf(currentTheme);
      currentTheme = THEMES[(idx + 1) % THEMES.length];
      document.cookie = `theme=${currentTheme};max-age=31536000;path=/`;
      applyTheme(currentTheme);
    });
  }
}

/* ─── HOVER LIGHTING ─── */
function initHoverEffect() {
  document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
      card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
    });
  });
}

/* ─── INIT ─── */
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initHoverEffect();
  initArea();
  initBar();
  runCounters();

  const btn3d = document.getElementById('btn3dToggle');
  if (btn3d) btn3d.addEventListener('click', toggle3DMode);
});