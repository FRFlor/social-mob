<template>
    <div v-if="growthSessions.isReady">
        <div class="flex justify-center items-center text-xl text-blue-600 font-bold">
            <button aria-label="Load previous week"
                    class="load-previous-week mx-4 mb-2"
                    @click="changeReferenceDate(-7)">
                <i aria-hidden="true" class="fa fa-chevron-left"></i>
            </button>
            <h2 v-if="growthSessions.weekDates.length > 0" class="text-center mb-2 w-72">
                Week of
                {{ growthSessions.firstDay.format('MMM-DD') }} to
                {{ growthSessions.lastDay.format('MMM-DD') }}
            </h2>
            <button ref="load-next-week-button"
                    aria-label="Load next week"
                    class="load-next-week mx-4 mb-2"
                    @click="changeReferenceDate(+7)">
                <i aria-hidden="true" class="fa fa-chevron-right"></i>
            </button>
        </div>

        <div class="flex justify-center flex-wrap h-screen overflow-y-scroll shadow-inner border-b-2">
            <modal :dynamic="true" :height="600" :width="500" name="growth-session-form">
                <div class="flex w-full h-full">
                    <growth-session-form :growth-session="growthSessionToUpdate"
                                         :owner="user"
                                         :start-date="newGrowthSessionDate"
                                         class="growth-session-form"
                                         @submitted="onFormSubmitted"/>
                </div>
            </modal>
            <div v-for="date in growthSessions.weekDates"
                 :key="date.toDateString()"
                 :class="{
                 'bg-blue-100': date.weekDayNumber() % 2 === 0,
                 'bg-blue-200': date.weekDayNumber() % 2 !== 0,
                 }"
                 :weekDay="date.weekDayString()"
                 class="day text-center mx-1 mb-2 px-2 md:block">
                <h3 class="text-lg text-blue-700 font-bold mt-6 mb-3 sticky top-0 py-2 z-30"
                    :class="{
                 'bg-blue-100': date.weekDayNumber() % 2 === 0,
                 'bg-blue-200': date.weekDayNumber() % 2 !== 0,
                 }"
                    v-text="date.weekDayString()"></h3>
                <div v-show="growthSessions.getSessionByDate(date).length === 0" class="text-blue-600 text-lg my-4">
                    <p v-text="`${Nothingator.random()}...`"/>
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>

                <draggable :date="date"
                           :list="growthSessions.getSessionByDate(date)"
                           class="h-full w-full"
                           group="growth-sessions"
                           handle=".handle"
                           @change="onChange"
                           @end="onDragEnd">
                    <div v-for="growthSession in growthSessions.getSessionByDate(date)"
                         :key="growthSession.id">
                        <growth-session-card
                            :growth-session="growthSession"
                            :user="user"
                            class="mb-3 transform transition-transform duration-150"
                            @growth-session-updated="getAllGrowthSessionsOfTheWeek"
                            @edit-requested="onGrowthSessionEditRequested"
                            @delete-requested="getAllGrowthSessionsOfTheWeek"/>
                    </div>

                    <button
                        v-if="user && ! date.isInAPastDate()"
                        class="create-growth-session text-5xl h-20 w-20 text-blue-600 hover:text-blue-700 focus:text-blue-700 font-bold my-3"
                        @click="onCreateNewGrowthSessionClicked(date)">
                        <i aria-hidden="true" class="fa fa-plus-circle"></i>
                    </button>
                </draggable>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {IUser} from '../types';
import GrowthSessionCard from './GrowthSessionCard.vue';
import GrowthSessionForm from './GrowthSessionForm.vue';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {DateTime} from '../classes/DateTime';
import Draggable from 'vuedraggable';
import {GrowthSession} from '../classes/GrowthSession';
import {WeekGrowthSessions} from '../classes/WeekGrowthSessions';
import {Nothingator} from '../classes/Nothingator';

interface IGrowthSessionCardDragChange {
    added?: { element: GrowthSession, index: number }
    removed?: { element: GrowthSession, index: number }
}

@Component({
    components: {GrowthSessionForm, GrowthSessionCard, Draggable}
})
export default class WeekView extends Vue {
    @Prop({required: false, default: null}) user!: IUser;
    referenceDate: DateTime = DateTime.today();
    growthSessions: WeekGrowthSessions = WeekGrowthSessions.empty();
    newGrowthSessionDate: string = '';
    growthSessionToUpdate: GrowthSession | null = null;
    DateTime = DateTime;
    Nothingator = Nothingator;
    draggedGrowthSession!: GrowthSession;

    async created() {
        await this.refreshGrowthSessionsOfTheWeek()
        window.onpopstate = this.refreshGrowthSessionsOfTheWeek;
    }

    beforeDestroy() {
        window.onpopstate = null;
    }

    async refreshGrowthSessionsOfTheWeek() {
        this.useDateFromUrlAsReference();
        await this.getAllGrowthSessionsOfTheWeek();
    }

    useDateFromUrlAsReference() {
        const urlSearchParams = new URLSearchParams(window.location.search);
        this.referenceDate = urlSearchParams.has('date')
            ? DateTime.parseByDate(urlSearchParams.get('date')!)
            : DateTime.today();
    }

    async onDragEnd(location: any) {
        let targetDate = location.to.__vue__.$attrs.date;
        try {
            await GrowthSessionApi.update(this.draggedGrowthSession, {date: targetDate.toDateString()});
        } catch (e) {
        }

        await this.getAllGrowthSessionsOfTheWeek();
    }

    onChange(change: IGrowthSessionCardDragChange) {
        if (change.added) {
            return this.draggedGrowthSession = change.added.element;
        }
    }

    async getAllGrowthSessionsOfTheWeek() {
        this.growthSessions = await GrowthSessionApi.getAllGrowthSessionsOfTheWeek(this.referenceDate.toDateString());
    }

    async onFormSubmitted() {
        await this.getAllGrowthSessionsOfTheWeek();
        this.$modal.hide('growth-session-form');
    }

    onCreateNewGrowthSessionClicked(startDate: DateTime) {
        this.growthSessionToUpdate = null;
        this.newGrowthSessionDate = startDate.toISOString();
        this.$modal.show('growth-session-form');
    }

    onGrowthSessionEditRequested(growthSession: GrowthSession) {
        this.growthSessionToUpdate = growthSession;
        this.newGrowthSessionDate = '';
        this.$modal.show('growth-session-form');
    }

    async changeReferenceDate(deltaDays: number) {
        this.referenceDate.addDays(deltaDays);
        window.history.pushState({}, document.title, `?date=${this.referenceDate.toDateString()}`);
        await this.getAllGrowthSessionsOfTheWeek();
    }
}
</script>

<style lang="scss" scoped>
.day {
    width: 20rem;
    min-height: 35rem;
}
</style>
