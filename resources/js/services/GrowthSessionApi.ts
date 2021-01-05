import BaseApi from './BaseApi';
import {
    IComment,
    IGrowthSession,
    IStoreGrowthSessionRequest,
    IUpdateGrowthSessionRequest,
    IWeekGrowthSessions
} from '../types';
import {DateTime} from '../classes/DateTime';
import {GrowthSession} from '../classes/GrowthSession';
import {WeekGrowthSessions} from '../classes/WeekGrowthSessions';

export const growthSessionResource = 'growth_sessions';

export class GrowthSessionApi extends BaseApi {
    static async getAllGrowthSessionsOfTheWeek(date: string = DateTime.today().toDateString()): Promise<WeekGrowthSessions> {
        let dateString = DateTime.parseByDate(date).toDateString();
        let response = await BaseApi.httpRequest.get<IWeekGrowthSessions>(`/${growthSessionResource}/week?date=${dateString}`);
        return new WeekGrowthSessions(response.data);
    }

    static async store(payload: IStoreGrowthSessionRequest): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post<IGrowthSession>(`/${growthSessionResource}`, payload);
        return new GrowthSession(response.data);
    }

    static async update(target: IGrowthSession, payload: IUpdateGrowthSessionRequest): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.put<IGrowthSession>(`/${growthSessionResource}/${target.id}`, payload);
        return new GrowthSession(response.data);
    }

    static async delete(target: IGrowthSession): Promise<boolean> {
        await BaseApi.httpRequest.delete(`/${growthSessionResource}/${target.id}`);
        return true;
    }

    static async join(target: IGrowthSession): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/${growthSessionResource}/${target.id}/join`);
        return new GrowthSession(response.data);
    }

    static async leave(target: IGrowthSession): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/${growthSessionResource}/${target.id}/leave`);
        return new GrowthSession(response.data);
    }

    static async postComment(target: IGrowthSession, content: string): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/${growthSessionResource}/${target.id}/comments`, {content});
        return new GrowthSession(response.data);
    }

    static async deleteComment(comment: IComment): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.delete(`/${growthSessionResource}/${comment.social_mob_id}/comments/${comment.id}`);
        return new GrowthSession(response.data);
    }

    static editUrl(target: IGrowthSession): string {
        return `/${growthSessionResource}/${target.id}/edit`
    }

    static showUrl(target: IGrowthSession): string {
        return `/${growthSessionResource}/${target.id}`;
    }
}
