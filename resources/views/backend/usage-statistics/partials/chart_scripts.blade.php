@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    window.usageCharts = [];
    window.usagePalette = ['#84cc16', '#10b981', '#f59e0b', '#14b8a6', '#f97316', '#f43f5e', '#d946ef', '#a855f7', '#78716c', '#eab308', '#ef4444', '#22c55e', '#4d7c0f', '#b45309', '#9d174d'];
    window.usageAddChart = function (id, config) {
      window.usageCharts.push({ id: id, config: config });
    };
    document.addEventListener('DOMContentLoaded', function () {
      window.usageCharts.forEach(function (c) {
        var el = document.getElementById(c.id);
        if (el) { new Chart(el, c.config); }
      });
    });
  </script>
@endpush
