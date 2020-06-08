import moment, {Moment} from 'moment';

export class DateTime {
    private dateTime: string;
    private static nowString: string = (new Date()).toISOString();

    constructor(dateTime?: string) {
        this.dateTime = dateTime || DateTime.nowString;
    }

    static parseByDate(date: string): DateTime {
        return new DateTime(moment(date, 'YYYY-MM-DD').toISOString());
    }

    static parseByTime(time: string): DateTime {
        const is12hrsFormat = /(am|pm)/gi.test(time);
        return new DateTime(moment(time, `hh:mm${is12hrsFormat ? ' a' : ''}`).toISOString(true));
    }

    static today() {
        return new DateTime();
    }

    static setTestNow(date: string) {
        DateTime.nowString = moment(date).toISOString();
    }

    format(formatString: string): string {
        return moment(this.dateTime, 'YYYY-MM-DD').format(formatString);
    }

    toDateString(): string {
        return this.format('YYYY-MM-DD');
    }

    toTimeString12Hours(withAmPm: boolean = true): string {
        return moment(this.dateTime).format(`hh:mm ${withAmPm ? 'a' : ''}`);
    }

    toISOString(): string {
        return moment(this.dateTime).toISOString();
    }

    weekDayNumber(): number {
        return moment(this.dateTime).weekday();
    }

    weekDayString(): string {
        return this.format('dddd');
    }

    addDays(days: number): DateTime {
        this.dateTime = moment(this.dateTime).add(days, 'days').toISOString();
        return this;
    }

    isSameDay(date: DateTime): boolean {
        return moment(this.dateTime).isSame(date.toDateString(), 'days');
    }

    isInAPastDate(): boolean {
        const current: Moment = moment(this.toDateString());
        const today: Moment = moment(DateTime.today().toDateString());
        return current.isBefore(today);
    }
}