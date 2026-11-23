@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    window.usageCharts = [];
    window.usagePalette = ['#84cc16', '#10b981', '#f59e0b', '#14b8a6', '#f97316', '#f43f5e', '#d946ef', '#a855f7', '#78716c', '#eab308', '#ef4444', '#22c55e', '#4d7c0f', '#b45309', '#9d174d'];
    window.usageAddChart = function (id, config) {
      window.usageCharts.push({ id: id, config: config });
    };
    window.usageChartInstances = {};
    window.usageTextColor = '#52525b';
    window.usageGridColor = '#e4e4e7';
    window.usageAxisTitle = function (text) {
      return { display: true, text: text, color: '#3f3f46', font: { size: 12, weight: 'bold' }, padding: 6 };
    };
    window.usageOptions = function (o) {
      var scales = {
        x: { stacked: !!o.stacked, beginAtZero: !!o.horizontal, ticks: { color: window.usageTextColor, precision: 0 }, grid: { color: window.usageGridColor }, title: window.usageAxisTitle(o.xTitle) },
        y: { stacked: !!o.stacked, beginAtZero: true, ticks: { color: window.usageTextColor, precision: 0 }, grid: { color: window.usageGridColor }, title: window.usageAxisTitle(o.yTitle) }
      };
      var options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: scales,
        plugins: {
          title: { display: true, text: o.title, color: '#27272a', font: { size: 14, weight: 'bold' }, padding: { top: 6, bottom: 14 } },
          legend: o.legend === false ? { display: false } : { position: 'bottom', labels: { color: window.usageTextColor } }
        }
      };
      if (o.horizontal) { options.indexAxis = 'y'; }
      return options;
    };
    window.usagePngFilename = function (id) {
      var by = @js($year === null ? 'by-year' : 'by-month');
      var names = {
        'chart-searches': 'searches-' + by,
        'chart-downloads': 'downloads-' + by,
        'chart-users': 'top20-users-by-searches',
        'chart-logins-month': 'logins-' + by,
        'chart-logins-geo': 'logins-by-ip-country',
        'chart-logins-profile': 'users-by-profile-country'
      };
      var scope = @js(isset($exportParams['user']) ? 'user-'.$exportParams['user'] : (isset($exportParams['guest']) ? 'guest' : 'all-users'));
      var period = @js($year === null ? 'all-years' : (string) $year);
      var d = new Date();
      var pad = function (n) { return String(n).padStart(2, '0'); };
      var stamp = d.getFullYear() + pad(d.getMonth() + 1) + pad(d.getDate()) + '-' + pad(d.getHours()) + pad(d.getMinutes()) + pad(d.getSeconds());
      return ['nds-usage', names[id] || id.replace(/^chart-/, ''), scope, period, stamp].join('_') + '.png';
    };
    window.usageDownloadPng = function (id) {
      var chart = window.usageChartInstances[id];
      if (!chart) { return; }
      var source = chart.canvas;
      var canvas = document.createElement('canvas');
      canvas.width = source.width;
      canvas.height = source.height;
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(source, 0, 0);
      var link = document.createElement('a');
      link.href = canvas.toDataURL('image/png');
      link.download = window.usagePngFilename(id);
      document.body.appendChild(link);
      link.click();
      link.remove();
    };
    document.addEventListener('DOMContentLoaded', function () {
      window.usageCharts.forEach(function (c) {
        var el = document.getElementById(c.id);
        if (el) { window.usageChartInstances[c.id] = new Chart(el, c.config); }
      });
    });
  </script>
@endpush
