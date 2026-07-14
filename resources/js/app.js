import Chart from 'chart.js/auto';
import { offlineDraft } from './offline.js';

// Dipakai komponen dashboard via Alpine x-init (lihat resources/views/livewire/dashboard.blade.php)
window.Chart = Chart;

// Komponen Alpine entri draft offline (M5). Alpine di-bundle Livewire 4.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('offlineDraft', offlineDraft);
});
