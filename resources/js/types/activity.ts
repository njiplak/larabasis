import type { User } from './auth';

export type Activity = {
    id: number;
    log_name: string | null;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    event: string | null;
    causer_id: number | null;
    causer: Pick<User, 'id' | 'name' | 'email'> | null;
    properties: Record<string, unknown> | null;
    created_at: string;
};
