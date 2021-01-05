<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->setTestNow('2020-01-08T15:00:00');
        $this->enableHooks();
    }

    public function testItHitsTheGrowthSessionDeletedTodayWebHookWheneverAGrowthSessionIsDeletedToday()
    {
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $user = $growthSession->owner;
        $this->actingAs($user)->deleteJson(route('growth_sessions.destroy', $growthSession))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.deleted_today');
        });
    }

    public function testItDoesNotHitTheGrowthSessionDeletedTodayWebHookIfTheGrowthSessionWasDeletedAtAnyOtherDay()
    {
        $growthSession = GrowthSession::factory()->create(['date' => today()->addDay()]);
        $user = $growthSession->owner;
        $this->actingAs($user)->deleteJson(route('growth_sessions.destroy', $growthSession))->assertSuccessful();

        Http::assertNothingSent();
    }

    public function testItDoesNotHitTheWebHookIfTheHookIsNotDefined()
    {
        $this->disableHooks();
        $growthSession = GrowthSession::factory()->create();
        $user = $growthSession->owner;
        $this->actingAs($user)->deleteJson(route('growth_sessions.destroy', $growthSession))->assertSuccessful();

        Http::assertNothingSent();
    }

    public function testItDoesNotBreakIfTheHookFails()
    {
        Http::fake(function () {
            return Http::response('Oh no, the webhook failed! :(', Response::HTTP_INTERNAL_SERVER_ERROR);
        });
        $growthSession = GrowthSession::factory()->create();
        $user = $growthSession->owner;

        $this->actingAs($user)
            ->deleteJson(route('growth_sessions.destroy', $growthSession))->assertSuccessful();
    }

    public function testItHitsTheGrowthSessionUpdatedTodayWebHookWheneverAGrowthSessionIsUpdatedToday()
    {
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $user = $growthSession->owner;
        $this->actingAs($user)->putJson(route('growth_sessions.update', $growthSession), ['topic' => 'new topic'])->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    public function testItHitsTheUpdatedTodayWebHookIfAGrowthSessionChangedItsDateToToday()
    {
        $growthSession = GrowthSession::factory()->create(['date' => today()->addDay()]);
        $user = $growthSession->owner;
        $this->actingAs($user)->putJson(route('growth_sessions.update', $growthSession), ['date' => today()])->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    public function testItHitsTheCreatedTodayWebHookIfAGrowthSessionWasCreatedForToday()
    {
        $this->setTestNow('2020-01-01T10:30:00.000');
        $user = User::factory()->create();
        $growthSessionData = GrowthSession::factory()->make(['date' => today()])->toArray();
        $this->actingAs($user)->postJson(route('growth_sessions.store'), $growthSessionData)->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.created_today');
        });
    }

    public function testItHitsTheAttendeesWebHookIfSomeoneJoinsAGrowthSessionThatWillHappenToday()
    {
        $this->withoutExceptionHandling();
        $growthSession = GrowthSession::factory()->create();
        $newMember = User::factory()->create();

        $this->actingAs($newMember)->postJson(route('growth_sessions.join', $growthSession))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.attendees_today');
        });
    }


    public function testItHitsTheAttendeesWebHookIfSomeoneLeavesAGrowthSessionThatWillHappenToday()
    {
        $growthSession = GrowthSession::factory()->create();
        /** @var GrowthSession $growthSession */
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee);

        $this->actingAs($attendee)->postJson(route('growth_sessions.leave', $growthSession))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.attendees_today');
        });
    }


    /**
     * @dataProvider providesActionsOutsideOfWebHookTimes
     */
    public function testItDoesNotSendWebHookNotificationsOutsideOfTheSetStartAndEndTimes(
        $startTime,
        $endTime,
        $requestTime
    )
    {
        $this->setTestNow("2020-01-01 {$requestTime}");
        Config::set('webhooks.start_time', $startTime);
        Config::set('webhooks.end_time', $endTime);
        $user = User::factory()->create();
        $growthSessionData = GrowthSession::factory()->make(['date' => today()])->toArray();

        $growthSession = $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $growthSessionData)->assertSuccessful();
        $this->actingAs($user)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession['id']]), ['topic' => 'new topic'])->assertSuccessful();
        $this->actingAs($user)
            ->deleteJson(route('growth_sessions.destroy', ['growth_session' => $growthSession['id']]))->assertSuccessful();

        Http::assertNothingSent();
    }

    public function providesActionsOutsideOfWebHookTimes()
    {
        $startTime = '09:30 am';
        $endTime = '05:00 pm';
        return [
            'The request happened before the start time' => [$startTime, $endTime, '08:00am'],
            'The request happened after the end time' => [$startTime, $endTime, '06:00pm'],
        ];
    }

    public function testItIncludesTheOwnerInformationOnThePayload()
    {
        $user = User::factory()->create();
        $growthSessionData = GrowthSession::factory()->make(['date' => today()])->toArray();
        $this->actingAs($user)->postJson(route('growth_sessions.store'), $growthSessionData)->assertSuccessful();

        Http::assertSent(function (Request $request) use ($growthSessionData) {
            return !empty($request->data()['owner']) && $request->data()['location'] === $growthSessionData['location'];
        });
    }

    private function enableHooks(): void
    {
        Config::set('webhooks.deleted_today', 'http://foobar.test/deleted');
        Config::set('webhooks.updated_today', 'http://foobar.test/updated');
        Config::set('webhooks.created_today', 'http://foobar.test/created');
        Config::set('webhooks.attendees_today', 'http://foobar.test/join_leave');
    }

    private function disableHooks(): void
    {
        Config::set('webhooks.deleted_today', null);
        Config::set('webhooks.updated_today', null);
        Config::set('webhooks.created_today', null);
        Config::set('webhooks.attendees_today', null);
    }
}
