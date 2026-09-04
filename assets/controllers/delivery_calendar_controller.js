import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'calendar',
        'day',
        'grid',
        'input',
        'monthLabel',
        'previous',
        'selection',
        'submit',
    ];

    connect() {
        this.minimumDate = this.parseDate(this.calendarTarget.dataset.minimumDate);
        this.currentMonth = this.parseDate(this.calendarTarget.dataset.currentMonth);
        this.currentMonth.setUTCDate(1);
        this.renderMonth();
    }

    previousMonth() {
        this.currentMonth.setUTCMonth(this.currentMonth.getUTCMonth() - 1);
        this.renderMonth();
    }

    nextMonth() {
        this.currentMonth.setUTCMonth(this.currentMonth.getUTCMonth() + 1);
        this.renderMonth();
    }

    select(event) {
        const button = event.currentTarget;
        const date = button.dataset.date;

        this.dayTargets.forEach((day) => {
            const selected = day === button;
            day.classList.toggle('is-selected', selected);
            day.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        this.inputTarget.value = date;
        this.selectionTarget.textContent = new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'long',
            timeZone: 'Europe/Paris',
        }).format(new Date(`${date}T12:00:00Z`));
        this.submitTarget.disabled = false;
    }

    renderMonth() {
        const monthStart = new Date(this.currentMonth);
        const firstWeekday = (monthStart.getUTCDay() + 6) % 7;
        const gridStart = new Date(monthStart);
        gridStart.setUTCDate(gridStart.getUTCDate() - firstWeekday);
        const selectedDate = this.inputTarget.value;

        this.monthLabelTarget.textContent = new Intl.DateTimeFormat('fr-FR', {
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(monthStart);
        this.gridTarget.setAttribute('aria-label', this.monthLabelTarget.textContent);
        this.gridTarget.replaceChildren();

        for (let offset = 0; offset < 42; offset += 1) {
            const date = new Date(gridStart);
            date.setUTCDate(gridStart.getUTCDate() + offset);

            const dateValue = this.formatDate(date);
            const currentMonth = date.getUTCMonth() === monthStart.getUTCMonth()
                && date.getUTCFullYear() === monthStart.getUTCFullYear();
            const available = currentMonth && date >= this.minimumDate;
            const button = document.createElement('button');

            button.type = 'button';
            button.className = `delivery-calendar__day${currentMonth ? '' : ' delivery-calendar__day--outside'}`;
            button.dataset.date = dateValue;
            button.setAttribute('aria-label', new Intl.DateTimeFormat('fr-FR', {
                dateStyle: 'long',
                timeZone: 'UTC',
            }).format(date));
            button.textContent = String(date.getUTCDate());

            if (available) {
                button.dataset.deliveryCalendarTarget = 'day';
                button.dataset.action = 'click->delivery-calendar#select';
                if (dateValue === selectedDate) {
                    button.classList.add('is-selected');
                    button.setAttribute('aria-selected', 'true');
                }
            } else {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }

            this.gridTarget.append(button);
        }

        const minimumMonth = new Date(this.minimumDate);
        minimumMonth.setUTCDate(1);
        this.previousTarget.disabled = this.currentMonth <= minimumMonth;
    }

    parseDate(value) {
        return new Date(`${value}T00:00:00Z`);
    }

    formatDate(date) {
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }
}
