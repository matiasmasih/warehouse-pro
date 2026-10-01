<?php
// warehouse_footer.php - Shared footer
?>

    </div>
    <!-- End main-content -->
</div>
<!-- End main-container -->

<!-- ============================================ -->
<!-- FLATPICKR — date & time picker                -->
<!-- ============================================ -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

<style>
/* Flatpickr amber/navy theme */
.flatpickr-calendar {
    background: var(--card-bg);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
    font-family: 'Inter', sans-serif;
    border-radius: 12px;
}
.flatpickr-calendar.arrowTop:before,
.flatpickr-calendar.arrowTop:after,
.flatpickr-calendar.arrowBottom:before,
.flatpickr-calendar.arrowBottom:after {
    border-bottom-color: var(--card-bg);
    border-top-color: var(--card-bg);
}
.flatpickr-months,
.flatpickr-weekdays {
    background: var(--card-bg);
}
.flatpickr-current-month,
.flatpickr-monthDropdown-months,
.flatpickr-weekday,
.numInputWrapper span.arrowUp:after,
.numInputWrapper span.arrowDown:after {
    color: var(--text) !important;
}
.flatpickr-weekday {
    color: var(--text-muted) !important;
    font-weight: 600;
    font-size: .8rem;
}
.flatpickr-day {
    color: var(--text);
    border-radius: 8px;
}
.flatpickr-day:hover,
.flatpickr-day:focus {
    background: rgba(245,158,11,.15);
    border-color: rgba(245,158,11,.35);
}
.flatpickr-day.today {
    border-color: var(--primary);
    color: var(--primary);
    font-weight: 700;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange,
.flatpickr-day.selected:hover {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    font-weight: 700;
}
.flatpickr-day.flatpickr-disabled,
.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
    color: var(--text-muted);
    opacity: .45;
}
.flatpickr-prev-month svg,
.flatpickr-next-month svg,
.flatpickr-prev-month:hover svg,
.flatpickr-next-month:hover svg {
    fill: var(--primary);
}
.numInputWrapper:hover {
    background: rgba(245,158,11,.15);
}
.flatpickr-time {
    background: var(--card-bg);
    border-top: 1px solid var(--border);
}
.flatpickr-time input {
    color: var(--text) !important;
    background: transparent !important;
    font-weight: 600;
    font-size: 1rem;
}
.flatpickr-time input:hover,
.flatpickr-time input:focus {
    background: rgba(245,158,11,.12) !important;
}
.flatpickr-time .flatpickr-am-pm {
    color: var(--text) !important;
}
.flatpickr-time .numInputWrapper span.arrowUp:after,
.flatpickr-time .numInputWrapper span.arrowDown:after {
    border-bottom-color: var(--text-muted);
    border-top-color: var(--text-muted);
}
input.flatpickr-input {
    cursor: pointer;
}

/* Make Flatpickr alt inputs match form controls height */
.flatpickr-input,
.flatpickr-input.form-control,
input.flatpickr-input,
.flatpickr-alt-input {
    height: auto !important;
    min-height: 38px;
    padding: 8px 12px !important;
    border: 1px solid var(--border) !important;
    border-radius: 8px !important;
    background: var(--card-bg) !important;
    color: var(--text) !important;
    font-size: 1rem !important;
    line-height: 1.2 !important;
    box-sizing: border-box;
    display: inline-block;
}
.flatpickr-input:focus,
.flatpickr-alt-input:focus {
    outline: none;
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(245,158,11,.15);
}

/* Ensure buttons and inputs align in filter rows */
form > button.btn-add,
form > a.btn-add,
form > a.btn-secondary-custom,
form > button.btn-secondary-custom {
    min-height: 38px;
    padding: 8px 14px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-sizing: border-box;
}

</style>

<script>
// ============================================
// THEME TOGGLE
// ============================================
function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    if (html.getAttribute('data-theme') === 'dark') {
        html.removeAttribute('data-theme');
        icon.className = 'bi bi-moon-stars';
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        icon.className = 'bi bi-sun';
        localStorage.setItem('theme', 'dark');
    }
}

if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        themeIcon.className = 'bi bi-sun';
    }
}

// ============================================
// LIVE CLOCK (12-HOUR FORMAT)
// ============================================
function updateClock() {
    const clockEl = document.getElementById('clock');
    const dateEl = document.getElementById('date');
    if (clockEl && dateEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        dateEl.textContent = now.toLocaleDateString('en-GB', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}
setInterval(updateClock, 1000);
updateClock();

// ============================================
// COUNTER ANIMATION
// ============================================
document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll('.counter');
    if (counters.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute('data-target');
                const duration = 1500;
                const step = target / (duration / 16);
                let current = 0;

                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.innerText = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = target;
                    }
                };

                updateCounter();
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(counter => observer.observe(counter));
});

// ============================================
// FLATPICKR AUTO-INIT — upgrades every date/time input
// Server still receives: YYYY-MM-DD and HH:MM
// ============================================
document.addEventListener("DOMContentLoaded", function() {
    if (typeof flatpickr === 'undefined') return;

    // --- Dates ---
    document.querySelectorAll('input[type="date"]').forEach(function(el) {
        // Preserve the original name/format value before Flatpickr wraps it
        const currentValue = el.value;

        flatpickr(el, {
            dateFormat: 'Y-m-d',        // value sent to server
            altInput: true,
            altFormat: 'd-M-Y',         // visible format: 01-Oct-2026
            allowInput: true,           // allows typing
            defaultDate: currentValue || null,
            disableMobile: true,        // use Flatpickr even on mobile
            monthSelectorType: 'static'
        });
    });

    // --- Times ---
    document.querySelectorAll('input[type="time"]').forEach(function(el) {
        const currentValue = el.value;

        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',          // value sent to server: 24h
            time_24hr: true,            // visible: 24h too
            allowInput: true,
            defaultDate: currentValue || null,
            minuteIncrement: 15,        // 15-min steps, standard for shifts
            disableMobile: true
        });
    });

    // --- Datetime-local (in case you add any later) ---
    document.querySelectorAll('input[type="datetime-local"]').forEach(function(el) {
        const currentValue = el.value;
        flatpickr(el, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            time_24hr: true,
            allowInput: true,
            defaultDate: currentValue || null,
            disableMobile: true
        });
    });
});
</script>
</body>
</html>
