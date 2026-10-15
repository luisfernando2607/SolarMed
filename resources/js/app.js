import './bootstrap';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

window.Swal = Swal;
window.Chart = Chart;
window.FullCalendar = { Calendar, plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin], esLocale };
import './evolucion';
