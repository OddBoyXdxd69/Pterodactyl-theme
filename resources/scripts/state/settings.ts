import { action, Action } from 'easy-peasy';

export interface SiteSettings {
    name: string;
    locale: string;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
    theme?: {
        logo?: string;
        footer?: string;
        discord_url?: string;
        support_url?: string;
        announcement_enabled?: boolean;
        announcement_text?: string;
        announcement_type?: 'info' | 'warning' | 'critical';
        announcement_dismissible?: boolean;
    };
    registration?: {
        enabled: boolean;
    };
}

export interface SettingsStore {
    data?: SiteSettings;
    setSettings: Action<SettingsStore, SiteSettings>;
}

const settings: SettingsStore = {
    data: undefined,

    setSettings: action((state, payload) => {
        state.data = payload;
    }),
};

export default settings;
