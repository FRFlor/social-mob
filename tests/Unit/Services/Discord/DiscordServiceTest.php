<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordService;
use App\Services\Discord\Models\Channel;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordServiceTest extends TestCase
{
    private string $fakeDiscordGuildId = 'geralt_of_rivia';

    private string $fakeVidyaCategoryId = '111222333';

    public function setUp(): void
    {
        parent::setUp();
        config(['services.discord.guild_id' => $this->fakeDiscordGuildId]);
        config(['services.discord.vidya_id' => $this->fakeVidyaCategoryId]);
    }

    public function testItCanBePulledFromTheApplicationContainer(): void
    {
        /** @var DiscordService $discord */
        $discord = $this->app->get(DiscordService::class);

        $this->assertInstanceOf(DiscordService::class, $discord);
    }

    public function testItMakesAGetsRequestToDiscordForAllGuildVoiceChannels(): void
    {
        Http::fake();

        $discord = new DiscordService();

        $discord->getChannels();

        Http::assertSent(function(Request $request) {
            return $request->url() === "https://discord.com/api/guilds/$this->fakeDiscordGuildId/channels";
        });
    }

    public function testItReturnsACollectionOfAllDiscordGuildVoiceChannels(): void
    {
        $channelsFixture = $this->loadJsonFixture('Discord/Channels', true);
        Http::fake([
            '*' => Http::response($channelsFixture, Response::HTTP_OK)
        ]);

        $discord = new DiscordService();

        $channels = $discord->getChannels();

        $this->assertInstanceOf(Collection::class, $channels);
        $this->assertEquals(sizeof($channelsFixture), $channels->count());
        collect($channelsFixture)->each(function ($channelFixture) use ($channels) {
            $this->assertTrue($channels->pluck('id')->contains($channelFixture['id']));
        });
        $channels->each(function ($channel) {
            $this->assertInstanceOf(Channel::class, $channel);
        });
    }

    public function testItFiltersOutChannelsThatAreChildrenOfVidyaId(): void
    {
        $channelsFixture = $this->loadJsonFixture('Discord/Channels', true);
        $channelsFixture[1]['parent_id'] = '333222111';
        $channelsFixture[2]['parent_id'] = $this->fakeVidyaCategoryId;
        Http::fake([
            '*' => Http::response($channelsFixture, Response::HTTP_OK)
        ]);

        $discord = new DiscordService();

        $channels = $discord->getChannels();
        $this->assertEquals(2, $channels->count());
        $channels->each(function (Channel $channel) use ($channelsFixture) {
            $this->assertNotEquals($channelsFixture[2]['id'], $channel->id);
        });
    }

    public function testItFiltersOutVidyaCategory(): void
    {
        $channelsFixture = $this->loadJsonFixture('Discord/Channels', true);
        $channelsFixture[2]['id'] = $this->fakeVidyaCategoryId;
        Http::fake([
            '*' => Http::response($channelsFixture, Response::HTTP_OK)
        ]);

        $discord = new DiscordService();

        $channels = $discord->getChannels();
        $this->assertEquals(2, $channels->count());
        $channels->each(function (Channel $channel) use ($channelsFixture) {
            $this->assertNotEquals($channelsFixture[2]['id'], $channel->id);
        });
    }
}
