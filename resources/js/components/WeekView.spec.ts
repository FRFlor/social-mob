import {createLocalVue, mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import flushPromises from 'flush-promises';
import growthSessionsThisWeekJson from '../../../tests/fixtures/WeekGrowthSessions.json';
import {IUser} from '../types';
import VModal from 'vue-js-modal';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {DateTime} from '../classes/DateTime';
import {WeekGrowthSessions} from '../classes/WeekGrowthSessions';
import {GrowthSession} from '../classes/GrowthSession';
import {Nothingator} from '../classes/Nothingator';

const authUser: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer'
};

const localVue = createLocalVue();
localVue.use(VModal);
let growthSessionsThisWeek: WeekGrowthSessions = new WeekGrowthSessions(growthSessionsThisWeekJson);

const metadataForGrowthSessionsFixture = {
    today: {date: '2020-01-15', weekday: 'Wednesday'},
    dayWithNoGrowthSessions: {date: '2020-01-16', weekday: 'Tuesday'},
    nextWeek: {date: '2020-01-22', weekday: 'Wednesday'}
};

const todayDate: string = metadataForGrowthSessionsFixture.today.date;

describe('WeekView', () => {
    let wrapper: Wrapper<WeekView>;

    beforeEach(async () => {
        DateTime.setTestNow(todayDate);
        GrowthSessionApi.getAllGrowthSessionsOfTheWeek = jest.fn().mockResolvedValue(growthSessionsThisWeek);
        GrowthSessionApi.join = jest.fn().mockImplementation(growthSession => growthSession);
        GrowthSessionApi.leave = jest.fn().mockImplementation(growthSession => growthSession);
        GrowthSessionApi.delete = jest.fn().mockImplementation(growthSession => growthSession);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();
    });

    afterEach(() => {
        window.history.replaceState({}, document.title, 'localhost')
        jest.restoreAllMocks();
    });

    it('loads with the current week growth sessions in display', () => {
        const topicsOfTheWeek = growthSessionsThisWeek.allGrowthSessions.map((growthSession: GrowthSession) => growthSession.topic);
        for (let topic of topicsOfTheWeek) {
            expect(wrapper.text()).toContain(topic);
        }
    });


    it('allows the user to view growth sessions of the previous week', async () => {
        wrapper.find('button.load-previous-week').trigger('click');
        await flushPromises();
        let sevenDaysInThePast = DateTime.parseByDate(todayDate).addDays(-7).toDateString();
        expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(sevenDaysInThePast);
    });

    it('allows the user to view growth sessions of the next week', async () => {
        window.confirm = jest.fn();
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        let sevenDaysInTheFuture = DateTime.parseByDate(todayDate).addDays(7).toDateString();
        expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(sevenDaysInTheFuture);
    });

    it('shows only the current day in mobile devices', () => {
        window = Object.assign(window, {innerWidth: 300, open: () => null});

        let today = DateTime.today();
        let tomorrow = DateTime.today().addDays(1);
        expect(wrapper.find(`[weekDay=${today.weekDayString()}`).element).not.toHaveClass('hidden');
        expect(wrapper.find(`[weekDay=${tomorrow.weekDayString()}`).element).toHaveClass('hidden');
    });


    it('does not display the growth session creation buttons for guests', async () => {
        expect(wrapper.find('button.create-growth-session').exists()).toBe(false);
    });

    it('if no growth sessions are available on that day, display a variation of nothing in different languages', async () => {
        const wordForNothing = 'A random nothing';
        Nothingator.random = jest.fn().mockReturnValue(wordForNothing);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();

        expect(wrapper.find(`[weekDay=${metadataForGrowthSessionsFixture.dayWithNoGrowthSessions.weekday}`).text())
            .toContain(wordForNothing);
    });

    describe('for an authenticated user', () => {
        beforeEach(async () => {
            wrapper = mount(WeekView, {localVue, propsData: {user: authUser}});
            await flushPromises();
        });

        it('allows the user to create a growth session', async () => {
            wrapper.find('button.create-growth-session').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('form.create-growth-session').exists()).toBe(true);
        });

        it('does not display the growth session creation buttons for days in the past', async () => {
            const failPast = 'The create button was rendered in a past date';
            const failFuture = 'The create button was not rendered in a future date';
            expect(wrapper.find('[weekday=Monday] button.create-growth-session').exists(), failPast).toBe(false);
            expect(wrapper.find('[weekday=Tuesday] button.create-growth-session').exists(), failPast).toBe(false);

            expect(wrapper.find('[weekday=Wednesday] button.create-growth-session').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Thursday] button.create-growth-session').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Friday] button.create-growth-session').exists(), failFuture).toBe(true);
        });
    });

    describe('week persistence', () => {
        it('displays the current week of the day if no date value is provided in the url', () => {
            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.today.date);
        });

        it('displays the growth sessions of the week of the date provided in the query string if it exists', () => {
            window.history.pushState({}, 'sometitle', `?date=${metadataForGrowthSessionsFixture.nextWeek.date}`)
            wrapper = mount(WeekView, {localVue});
            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.nextWeek.date);
        });

        it('updates the query string for the date whenever the user navigates the weeks', async () => {
            wrapper.findComponent({ref: 'load-next-week-button'}).trigger('click');
            await flushPromises();

            const urlParameters: URLSearchParams = new URLSearchParams(window.location.search);
            expect(urlParameters.get('date')).toEqual(metadataForGrowthSessionsFixture.nextWeek.date);
        });

        it('properly display the growth sessions of the day from the query string when the user navigates back in history', async () => {
            window.history.pushState({}, 'sometitle', `?date=${metadataForGrowthSessionsFixture.nextWeek.date}`)
            wrapper = mount(WeekView, {localVue});
            await flushPromises();
            GrowthSessionApi.getAllGrowthSessionsOfTheWeek = jest.fn();

            window.history.back =  () => {
                console.error = jest.fn()
                const fakeMockEvent = {} as unknown as PopStateEvent;
                window.onpopstate!(fakeMockEvent);
            }

            window.history.back();
            await flushPromises();

            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.nextWeek.date);
        });
    });
});
